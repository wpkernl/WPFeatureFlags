<?php

namespace kernl;

class WPFeatureFlags {

  private $failActive;
  private $flagCount = 0;
  private $flags = [];
  private $productKey;
  private $salt = '1';
  private $userIdentifier;

  public function __construct($productKey, $userIdentifier, $failActive = false) {
      $this->productKey = $productKey;
      $this->userIdentifier = $userIdentifier;
      $this->failActive = $failActive;
      $this->flags = get_transient($this->getFlagKey());
  }

  public function active($flagIdentifier) {

      // Does not have flags or key doesn't exist.
      if (!$this->hasFlags() || !$this->flagExists($flagIdentifier)) {
          $lastChecked = get_transient($this->getLastCheckKey());

          // No last checked transient or it's been more than 5 minutes since last check
          if (!$lastChecked || ((time() - $lastChecked) > 60 * 5)) {
              $this->updateFlags();

              // Flags updated, lets see if we have anything new.
              if (!$this->hasFlags() || !$this->flagExists($flagIdentifier)) {
                  return $this->failActive;
              } else {
                  return $this->flags[$flagIdentifier];
              }

          } else {
              return $this->failActive;
          }

      // Flag found, return.
      } else {
          return $this->flags[$flagIdentifier];
      }
  }

  private function getFlagKey() {
      $hash = md5("{$this->productKey}-{$this->userIdentifier}-{$this->salt}");
      return "kernlff-{$hash}";
  }

  private function getLastCheckKey() {
      $hash = md5("{$this->productKey}-{$this->userIdentifier}-{$this->salt}");
      return "kernlchk-{$hash}";
  }

  private function flagExists($flagIdentifier) {
      return isset($this->flags[$flagIdentifier]);
  }

  private function hasFlags() {
      return $this->flagCount === 0;
  }

  private function updateFlags() {
      $headers = array('Content-Type' => 'application/json');
      $url = "https://kernl.us/api/v2/public/feature-flags/$this->productKey?identifier=$this->userIdentifier";
      $response = wp_remote_get($url);
      if (is_array($response) && $response['response']['code'] === 200) {
          try {
              $decodedFlags = json_decode($response['body']);
              foreach ($decodedFlags as $flag) {
                  $this->flags[$flag->flag] = $flag->active;
                  $this->flagCount++;
              }

              // Update last checked.
              set_transient($this->getLastCheckKey(), time(), 60 * 5);

              // Update flags transient
              set_transient($this->getFlagKey(), $this->flags, 60 * 60 * 24);

          } catch(Exception $err) {
              // echo " There was an error de-serializing the feature flag request body.";
          }

      } else {
          // echo "There was an error in your feature flag request.";
      }
  }
}