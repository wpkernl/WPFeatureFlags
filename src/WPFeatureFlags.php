<?php

namespace kernl;

class WPFeatureFlags {

  private $cacheTimeMinutes;
  private $failActive;
  private $productKey;
  private $userIdentifier;

  public function __construct($productKey, $userIdentifier, $failActive = false, $cacheTimeMinutes = 5) {
      $this->cacheTimeMinutes = $cacheTimeMinutes;
      $this->productKey = $productKey;
      $this->userIdentifier = $userIdentifier;
      $this->failActive = $failActive;
  }

  public function active($flagIdentifier) {
      $hash = md5("{$this->productKey}-{$this->userIdentifier}");
      $flagKey = "kff-${hash}";
      $flags = get_transient($flagKey);
      if (!$flags || count($flags) == 0) {
        $this->updateFlags($flagKey);
        $flags = get_transient($flagKey);
      }
      if (!$flags || count($flags) == 0 || !isset($flags[$flagIdentifier])) {
        return $this->failActive;
      } else {
        return $flags[$flagIdentifier];
      }

  }

  private function updateFlags($flagKey) {
      $headers = array('Content-Type' => 'application/json');
      $url = "https://kernl.us/api/v2/public/feature-flags/$this->productKey?identifier=$this->userIdentifier";
      $response = wp_remote_get($url);
      if (is_array($response) && $response['response']['code'] === 200) {
          try {
              $decodedFlags = json_decode($response['body']);
              $flags = array();
              foreach ($decodedFlags as $flag) {
                  $flags[$flag->flag] = $flag->active;
              }
              set_transient($flagKey, $flags, 60 * $this->cacheTimeMinutes);
          } catch(Exception $err) {
            //   echo " There was an error de-serializing the feature flag request body.";
          }
      } else {
        //   echo "There was an error in your feature flag request.";
      }
  }
}