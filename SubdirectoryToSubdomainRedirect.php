<?php

/*
Plugin Name:    Subdirectory to subdomain redirect
Description:    Redirects any sudirectory call that matches an existing subdomain in a network install.
Version:        1.0.0
Author:         Sebastian Thulin, Joel Bernerman
*/

namespace SubdirectoryToSubdomainRedirect;

class SubdirectoryToSubdomainRedirect
{
    public $activeRedirectBlogs = [1];

    public function __construct()
    {
        if (defined('SUBDIRECTORY_TO_SUBDOMAIN_REDIRECT_ACTIVE_BLOGS') && isarray(SUBDIRECTORY_TO_SUBDOMAIN_REDIRECT_ACTIVE_BLOGS)) {
            $this->activeRedirectBlogs = SUBDIRECTORY_TO_SUBDOMAIN_REDIRECT_ACTIVE_BLOGS;
        }

        add_action('wp_loaded', array($this, 'init'));
    }


    /**
     * Initializes the redirect process by getting the sites and stuff.
     * @return void
     */
    public function init()
    {
        // Only redirect from active blogs;
        if (!in_array(get_current_blog_id(), $this->activeRedirectBlogs)) {
            return;
        }

        $response = $this->getSites();

        $response = apply_filters('siteRedirect', $response);

        $this->maybeRedirect($response);
    }

    /**
     * Redirect if needed.
     * @param  array $sites List of sites
     * @return bool
     */
    public function maybeRedirect(array $sites) : bool
    {
        $currentPath = trim($this->currentPath(), '/');

        foreach ($sites as $site) {

            $subdomain = explode('.', $site->domain);
            $subdomain = reset($subdomain);

            if (substr($currentPath, 0, strlen($subdomain)) != $subdomain) {
                continue;
            }

            wp_redirect('https://' . $site->domain, '302');
            exit;
        }
        return false;
    }

    /**
     * Get the current url path.
     * @return string
     */
    public function currentPath() : string
    {
        return trailingslashit($_SERVER['REQUEST_URI']);
    }

    /**
     * Get list of sites.
     * @return array
     */
    public function getSites() : array
    {
        $response = get_sites(array('number' => 0));

        if (!$response) {
            return array();
        }

        // Remove active redirect sites from result.
        $response = array_filter($response, function ($site) {
            return !in_array($site->blog_id, $this->activeRedirectBlogs);
        });
        return $response;
    }
}

new \SubdirectoryToSubdomainRedirect\SubdirectoryToSubdomainRedirect();
