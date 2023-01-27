<?php
namespace Arhamlabs\PaymentGateway\traits;


trait ApiCall
{
    public static function postCall($url, $data, $encodeRazorKey)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Basic ' . $encodeRazorKey]);
        $response = curl_exec($ch);
        curl_close($ch);
        return $response;
    }

    public static function getCall($url, $id, $encodeRazorKey)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url . $id);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        // curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($orderData));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Basic ' . $encodeRazorKey]);
        $response = curl_exec($ch);
        curl_close($ch);
        return $response;
    }
}