<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;

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
            return response()->json(['error' => 'invalid member inputs']);
        }

        //shorten url
        $wUrlShort = $this->ShortenUrl($wUrl);

        $stmt = $this->db->prepare("
            insert into Members (Name, WebsiteUrl, WebsiteUrlShortened) values (?,?,?)
        ");
        $stmt->execute([$name, $wUrl, $wUrlShort]);
        return response()->json($this->db->lastInsertId());
    }

    public function ViewMember(): JsonResponse
    {
        return response()->json([]);
    }

    public function ListMembers(): JsonResponse
    {
        return response()->json([]);
    }

    private function ShortenUrl($url): string
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://git.io');
//        curl_setopt($ch, CURLOPT_POST, count($params));
        curl_setopt($ch, CURLOPT_POSTFIELDS, "url={$url}");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);
        if ($result === false) {
            exit('Curl error: ' . curl_error($ch));
        }
        curl_close($ch);

        return $result;
    }
}
