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

        $memberId = $this->db->lastInsertId();

        //scrape
        if(!$this->Scrape($wUrl, $memberId)){
            return response()->json([
                'success' => false,
                'error' => "could not scrape headings from {$wUrl}"
            ]);
        }

        return response()->json([
            'success' => true,
            'memberId' => $memberId
        ]);
    }


    public function ViewMember(int $memberId): JsonResponse
    {
        //Viewing an actual member should display the name, website URL, shortening, website headings,
        //and links to their friends' pages.

        //get info and headings
        $memberInfo = $this->GetMemberInfo($memberId);

        //get friends
        $memberFriends = $this->GetMemberFriends($memberId);

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


    private function ShortenUrl(string $url): string
    {
        $api = new BitlyApi(env('BITLY_API_KEY'));
        $bitlink = new Bitlink($api);
        $result = $bitlink->createBitlink(['long_url' => $url]);

        $responseArray = $result->getResponseArray();
        return array_key_exists('link', $responseArray)
            ? $responseArray['link']
            : '';
    }

    private function Scrape(string $url, int $memberId): bool
    {
        $client = new Scraper();
        $crawler = $client->request('GET', $url);

        $headers = [];
        $crawler->filter('h1,h2,h3')->each(function ($node) use (&$headers) {
            $headers[] = [
                'type' => $node->nodeName(),
                'content' => $node->text()
            ];
        });

        return $this->PersistHeaders($headers, $memberId);
    }


    private function PersistHeaders(array $headers, int $memberId): bool
    {
        /*
         * todo: bulk insert instead of looping
         */
        $hasFailure = false;
        foreach ($headers as $header){
            $stmt = $this->db->prepare("
                insert into MemberHeadings (MemberId, HeadingType, Heading) values (?,?,?)
            ");
            if (!$stmt->execute([$memberId, $header['type'], $header['content']])){
                $hasFailure = true;
            }
        }

        return !$hasFailure;
    }


    private function GetMemberInfo(int $memberId): array
    {
        $stmt = $this->db->prepare("
            select
            *
            from Members m
            left join MemberHeadings mh on m.MemberId = mh.MemberId
            where m.MemberId = ?
        ");
        $stmt->execute([$memberId]);

        return $stmt->fetchAll();
    }


    private function GetMemberFriends(int $memberId): array
    {
        $stmt = $this->db->prepare("
            select
                mf.SecondMemberId as FriendId
            from Members m
            join MemberFriends mf on m.MemberId = mf.FirstMemberId
            where m.MemberId = ?
        ");
        $stmt->execute([$memberId]);

        return $stmt->fetchAll();
    }
}
