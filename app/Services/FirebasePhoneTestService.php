<?php
namespace App\Services;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Auth as FirebaseAuth;
use Illuminate\Support\Facades\Log;

class FirebasePhoneTestService{
    protected FirebaseAuth $auth;

    public function __construct()
    {
        try{
             $factory = (new Factory)
            ->withServiceAccount(config('firebase.credentials'));

            $this->auth = $factory->createAuth() ;
        }catch (\Exception $e) {
            Log::error('Firebase initialization failed: ' . $e->getMessage());
            throw $e;
        }

    }
}
