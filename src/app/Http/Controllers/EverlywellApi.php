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
        if (!$this->Scrape($wUrl, $memberId)) {
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
        //get info and headings
        $memberInfo = $this->GetMemberInfo($memberId);

        //get friends
        $memberFriends = $this->GetMemberFriends($memberId);

        $output = [
            'name' => $memberInfo[0]['Name'],
            'websiteUrl' => $memberInfo[0]['WebsiteUrl'],
            'websiteUrlShort' => $memberInfo[0]['WebsiteUrlShortened'],
            'headings' => [],
            'friends' => []
        ];

        //hydrate headings (if there are any)
        if ($memberInfo[0]['Heading']) {
            foreach ($memberInfo as $datum) {
                $output['headings'][] = [
                    'type' => $datum['HeadingType'],
                    'text' => $datum['Heading']
                ];
            }
        }

        //hydrate friends
        foreach ($memberFriends as $friend) {
            $output['friends'][] = [
                'name' => $friend['FriendName'],
                'link' => '/viewMember/' . $friend['FriendId']
            ];
        }

        return response()->json($output);
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
        //we are using url parameters instead of post body here to save time
        // - parameters get builtin type checking

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


    public function Search(): JsonResponse
    {
        $term = $this->request->input('term');
        $mRef = $this->request->input('memberReference');

        //validate inputs
        if (!$term || !$mRef) {
            return response()->json([
                'success' => false,
                'error' => 'a search term and member reference are required'
            ]);
        }

        $stmt = $this->db->prepare("
            select
                mf2.FirstMemberId as FirstDegreeFriend,
                mf2.SecondMemberId as SecondDegreeFriend,
                mh.HeadingType,
                mh.Heading
            from MemberFriends mf
            left join MemberFriends mf2 on mf.SecondMemberId = mf2.FirstMemberId
            left join MemberHeadings mh on mf2.SecondMemberId = mh.MemberId
            where mf.FirstMemberId = ?
                and mf2.SecondMemberId != ?
                and mh.Heading like ?
        ");
        //the double wildcard on the like will ruin performance because an index can't be used
        //due to the wildcard before the term, but for demo purposes it is fine
        $stmt->execute([$mRef, $mRef, "%{$term}%"]);

        $output = [
            'success' => true,
            'results' => $stmt->fetchAll()
        ];

        return response()->json($output);
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
        foreach ($headers as $header) {
            $stmt = $this->db->prepare("
                insert into MemberHeadings (MemberId, HeadingType, Heading) values (?,?,?)
            ");
            if (!$stmt->execute([$memberId, $header['type'], $header['content']])) {
                $hasFailure = true;
            }
        }

        return !$hasFailure;
    }


    private function GetMemberInfo(int $memberId): array
    {
        $stmt = $this->db->prepare("
            select
                m.*,
                mh.HeadingType,
                mh.Heading
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
                mf.SecondMemberId as FriendId,
                m2.Name as FriendName
            from Members m
            join MemberFriends mf on m.MemberId = mf.FirstMemberId
            join Members m2 on mf.SecondMemberId = m2.MemberId
            where m.MemberId = ?
        ");
        $stmt->execute([$memberId]);

        return $stmt->fetchAll();
    }
}
