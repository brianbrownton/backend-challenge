<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use PHPLicengine\Api\Api as BitlyApi;
use PHPLicengine\Service\Bitlink;
use Goutte\Client as Scraper;

class EverlywellApi extends Controller
{

    public function test(): JsonResponse
    {
        return response()->json(['hi' => 'there']);
    }

    public function AddMember(): JsonResponse
    {
        $name = $this->request->input('name');
        $wUrl = $this->request->input('websiteUrl');

        //validate inputs
        if (
            !$name ||
            !$wUrl ||
            str_starts_with($wUrl, 'http') === false
        ) {
            return response()->json([
                'success' => false,
                'error' => 'invalid member inputs'
            ]);
        }

        //shorten url
        $wUrlShort = $this->ShortenUrl($wUrl);
        if (!$wUrlShort) {
            return response()->json([
                'success' => false,
                'error' => "bitly cannot shorten that url ({$wUrl})"
            ]);
        }

        //persist
        $stmt = $this->db->prepare("
            insert into Members (Name, WebsiteUrl, WebsiteUrlShortened) values (?,?,?)
        ");
        $stmt->execute([$name, $wUrl, $wUrlShort]);

        //scrape
        if(!$this->Scrape($wUrl)){
            return response()->json([
                'success' => false,
                'error' => "could not scrape headings from {$wUrl}"
            ]);
        }


        return response()->json(['success' => true]);
    }

    public function ViewMember(): JsonResponse
    {
        //Viewing an actual member should display the name, website URL, shortening, website headings,
        //and links to their friends' pages.
        return response()->json([]);
    }

    public function ListMembers(): JsonResponse
    {
        //get data
        $stmt = $this->db->prepare("
            select
                MemberId,
                Name,
                WebsiteUrlShortened,
                count(mf.FirstMemberId) as Friends
            from Members m
            left join MemberFriends mf on m.MemberId = mf.FirstMemberId
            group by MemberId
        ");
        $stmt->execute();

        return response()->json($stmt->fetchAll());
    }

    public function CreateFriendship(int $mIdOne, int $mIdTwo): JsonResponse
    {
        //make sure members exist and are distinct
        $stmt = $this->db->prepare("
            select MemberId from Members where MemberId in (?,?)
        ");
        $stmt->execute([$mIdOne, $mIdTwo]);
        $results = $stmt->fetchAll();

        if (count($results) !== 2) {
            return response()->json([
                'success' => false,
                'error' => 'please use two distinct member ids'
            ]);
        }

        //persist friendship twice for easier retrieval
        $stmt = $this->db->prepare("
            insert into MemberFriends (FirstMemberId, SecondMemberId) values (?,?),(?,?)
        ");
        $stmt->execute([$mIdOne, $mIdTwo, $mIdTwo, $mIdOne]);

        return response()->json(['success' => true]);
    }

    private function ShortenUrl($url): string
    {
        $api = new BitlyApi(env('BITLY_API_KEY'));
        $bitlink = new Bitlink($api);
        $result = $bitlink->createBitlink(['long_url' => $url]);

        $responseArray = $result->getResponseArray();
        return array_key_exists('link', $responseArray)
            ? $responseArray['link']
            : '';
    }

    private function Scrape($url): bool
    {
        $client = new Scraper();
        $crawler = $client->request('GET', $url);
        $crawler->filter('h1')->each(function ($node) {
            print $node->nodeName()."\n";
            print $node->text()."\n";
        });
    }
}
