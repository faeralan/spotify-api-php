<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

use GuzzleHttp\Client;
use GuzzleHttp\Promise\Promise;

namespace App\Controllers;

final class SpotifyController{
    
    public function index($request, $response){
        $band = $request->getQueryParams();
        if(isset($band['q']) && trim($band['q']) != ''){
            $artistId = $this->getArtistId($band['q']);
            
            if($artistId != 0){
                $discografy = $this->getArtistDiscografy($artistId);
                return $response->write(json_encode($discografy));
            }
        }else{
            return $response->write("Please complete the band's name");
    }
        
        
        
    }

    private function getArtistId($band){
        $access_token = $this->refreshToken();
        $client = new \GuzzleHttp\Client();
        
        $headers = [
            'Authorization' => 'Bearer '.$access_token,        
            'Content-Type'        => 'application/json',
        ];
        // Send an asynchronous request.
        $request = new \GuzzleHttp\Psr7\Request('GET', 'https://api.spotify.com/v1/search?q='.$band.'&type=artist');
        $promise = $client->sendAsync($request,[
            'headers' => $headers
        ])->then(function ($response) use (&$promise) {
            $res = $response->getBody()->getContents();
            $data = json_decode($res, true);
            
            $maxFollowers = 0;
            $artistId = 0;

            if(isset($data['artists'])){
                foreach($data['artists']['items'] as $k => $artist){
                    
                    $followers = $data['artists']['items'][$k]['followers']['total'];
                    $id = $data['artists']['items'][$k]['id'];
                    
                    if($followers > $maxFollowers){
                        $maxFollowers = $followers;
                        $artistId = $id;
                    }
                }
            }

            return $artistId;
        });

        return $promise->wait();

        
    }


    private function getArtistDiscografy($id){
        $access_token = $this->refreshToken();
        $client = new \GuzzleHttp\Client();
        
        $headers = [
            'Authorization' => 'Bearer '.$access_token,        
            'Content-Type'        => 'application/json',
        ];
        // Send an asynchronous request.
        $request = new \GuzzleHttp\Psr7\Request('GET', 'https://api.spotify.com/v1/artists/'.$id.'/albums');
        $promise = $client->sendAsync($request,[
            'headers' => $headers
        ])->then(function ($response) use (&$promise) {
            $res = $response->getBody()->getContents();
            $data = json_decode($res, true);
            
            $resultados = array();
            if(isset($data['items']) && count($data['items']) > 0){
                foreach ($data['items'] as $k => $album){
                    $new_album['name']=$data['items'][$k]['name'];
                    $new_album['released']=$data['items'][$k]['release_date'];
                    $new_album['tracks']=$data['items'][$k]['total_tracks'];
                    $new_album['cover']=$data['items'][$k]['images'][0];
                    
                    array_push($resultados, $new_album);
                }
                return $resultados;
            } 
        });

        return $promise->wait();
    }

    private function refreshToken(){
        $refresh_token = 'AQD-T75Vbs_CwLtzrS6VAG-ZnUt6tBg8f03d-gtqqbxdcGCF5eCIEcEWqF_9Okv9BeUUUbJtv7qyp8sVRrE1waaNCBdeTvfDHEZ29xlcIVxX5y8eyajOIUiuGROSKFt-08M';
        $client_id = '60fd3507256c4d6b9b42021da6c113eb';
        $client_secret = '5077a868a1a14d9392ca7b92d7d30257';

        $auth = 'Basic '.base64_encode($client_id.':'.$client_secret);
        

        $client = new \GuzzleHttp\Client();
        
        $params = [
            'headers' => [
                'Authorization' => $auth,
                'Content-Type' => 'application/x-www-form-urlencoded',
                'Accept' => 'application/json, text/plain, */*'
            ],
            'form_params' => [
                'grant_type' => 'refresh_token',
                'refresh_token' => $refresh_token
            ]
        ];
        // Send an asynchronous request.
        $request = new \GuzzleHttp\Psr7\Request('POST', 'https://accounts.spotify.com/api/token');
        $promise = $client->sendAsync($request, $params)->then(function ($response) use (&$promise) {
            $res = $response->getBody()->getContents();
            $data = json_decode($res, true);
            
            return $data['access_token'];
        });

        return $promise->wait();


    }

}