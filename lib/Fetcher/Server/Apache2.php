<?php

namespace Fetcher\Server;

class Apache2 {

  protected $container;

  public function __construct(\Pimple $container) {
    $this->container = $container;
  }

  /**
   * Implements \Fetcher\Server\ServerInterface::registerSettings().
   *
   * TODO: I think this is a good idea...
   */
  public function registerSettings(\Fetcher\Site $site) {
    $site['server.user'] = 'www-data';
    $site['server.basewebroot'] = '/var/www';
  }

  /**
   * Get the user under which this server runs.
   *
   * TODO: This can vary based on the system.
   */
  public function getWebUser() {
    return 'www-data';
  }

  /**
   * Get the parent folder where web files should be located.
   *
   * TODO: This can vary based on the system.
   */
  public function getWebRoot() {
    return '/var/www';
  }

  /**
   * Check whether this site appears to be enabled.
   *
   * TODO: This can vary based on the system.
   */
  public function siteEnabled() {
    return is_link('/etc/apache2/sites-enabled/' . $this->container['name']);
  }

  /**
   * Check whether this site appears to be configured and configure it if not.
   *
   * TODO: This can vary based on the system.
   */
  public function ensureSiteConfigured() {
    $container = $this->container;
    $vhostPath = $this->getVhostPath();
    if (!is_file($vhostPath)) {
      $vars = array(
        'site_name' => $container['name'],
        'hostname' => $container['hostname'],
        'site_folder' => $container['site.working_directory'],
      );
      $content = \drush_fetcher_get_asset('drupal.' . $container['version'] . '.vhost', $vars);
      $container['system']->writeFile($vhostPath, $content);
    }
  }

  /**
   * Get the path where vhost files should be placed.
   *
   * TODO: This can vary based on the system.
   */
  public function getVhostPath() {
    return '/etc/apache2/sites-available/' . $this->container['name'];
  }

  /**
   * Ensure that the site is removed.
   *
   * TODO: Vhost deletion can vary based on the system.
   */
  public function ensureSiteRemoved() {
    if ($this->siteEnabled()) {
      $this->ensureSiteDisabled();
      $this->restart();
    }
    $this->container['system']->ensureDeleted($this->getVhostPath());
  }

  /**
   * Ensure that the configured site has been enabled.
   *
   * TODO: This can vary based on the system.
   */
  public function ensureSiteEnabled() {
    $command = 'a2ensite ' . $this->container['name'];
    drush_log('Executing `' . $command . '`.');
    if (!drush_shell_exec($command)) {
      throw new \Fetcher\FetcherException(dt('The site @site could not be enabled.'), array('@site' => $this->container['name']));
    }
  }

  /**
   * Ensure that the configured site has been disabled.
   *
   * TODO: This can vary based on the system.
   */
  public function ensureSiteDisabled() {
    $command = 'a2dissite ' . $this->container['name'];
    drush_log('Executing `' . $command . '`.');
    if (!drush_shell_exec($command)) {
      throw new \Fetcher\FetcherException(dt('The site @site could not be disabled.', array('@site' => $this->container['name'])));
    }
  }

  /**
   * Restart the server to load the configuration.
   *
   * Note this should be done cracefully if possible.
   *
   * TODO: This can vary based on the system.
   */
  public function restart() {
    $command = 'sudo service apache2 reload';
    if (!drush_shell_exec($command)) {
      throw new \Fetcher\Exception\FetcherException(dt('Apache failed to restart, the server may be down.'));
    }
  }

}

