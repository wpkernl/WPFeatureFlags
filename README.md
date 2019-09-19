WP Feature Flags by Kernl.us
============================

This is the WordPress client library for Kernl feature flags.  It makes using feature flags easy and adds some WordPress specific performance optimizations.

### Installation

The best way to install the library is with Composer.

    composer require kernl/wp-feature-flags


### Usage

    require __DIR__ . '/vendor/autoload.php';

    // The feature flag product key.  This can be found in the Kernl web app
    // in the "Feature Flags" menu.
    $kernlFeatureFlagProductKey = '58cb023bc9689c1fe811615d';

    // The user identifier is how Kernl identifies the user requesting flags.
    // This should be unique for every user.
    $userIdentifier = 'jack@kernl.us';

    $kff = new kernl\WPFeatureFlags($kernlFeatureFlagProductKey, $userIdentifier);

    // This says "For the product defined above, does this flag exists,
    // and if so, is it active for the given user?".
    if ($kff->active('GITHUB_ON_OFF')) {
        add_action('admin_notices', 'feature_flag_active');
    }

    function feature_flag_active() {
        ?>
        <div class="notice notice-success is-dismissible">
            <p>Great work!  The feature flag is active.</p>
        </div>
        <?php
    }

### Performance Optimizations

This library makes some small performance optimizations by storing flag data for users as transients.  The default time length is 5 minutes, so your flag state will become eventually consistent.