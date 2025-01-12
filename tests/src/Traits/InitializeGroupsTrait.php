<?php

namespace Drupal\Tests\localgov_microsites_group\Traits;

use Drupal\group\Entity\Group;
use Drupal\Tests\domain\Traits\DomainTestTrait;

/**
 * Create microsite groups with domains.
 */
trait InitializeGroupsTrait {

  use DomainTestTrait;

  /**
   * Sets a base hostname for running tests.
   *
   * @var string
   */
  public $baseHostname;

  /**
   * Groups generated by createMicrositeGroups().
   *
   * @var \Drupal\group\Entity\Group[]
   */
  public $groups;

  /**
   * Generate $count groups.
   *
   * @param array $settings
   *   Keyed with entity property to set/override.
   * @param int $count
   *   Number of groups to create.
   *
   * @return \Drupal\group\Entity\Group[]
   *   Groups generated also assigned to $this->groups.
   */
  public function createMicrositeGroups(array $settings = [], int $count = 5) {
    for ($i = 1; $i <= $count; $i++) {
      $group = Group::create($settings + [
        'type' => 'microsite',
        'label' => $this->randomString(),
      ]);
      $group->enforceIsNew();
      $group->save();
      $this->groups[$i] = $group;
    }

    return $this->groups;
  }

  /**
   * Generate domains for groups.
   *
   * @param \Drupal\group\Entity\Group[] $groups
   *   Groups, usually generated by createMicrositeGroups().
   */
  public function createMicrositeGroupsDomains(array $groups) {
    $this->setBaseHostname();
    $domains = [''];
    foreach ($groups as $delta => $group) {
      $domains[] = [
        'subdomain' => 'group-' . $delta,
        'id' => 'group_' . $group->id(),
        'name' => $group->label(),
        'third_party_settings' => [
          'group_context_domain' => ['group_uuid' => $group->uuid()],
        ],
      ];
    }
    $this->domainCreateTestDomains($domains, count($domains));
  }

  /**
   * Generates a list of domains for testing.
   *
   * The script may also add test1, test2, test3 up to any number to test a
   * large number of domains.
   *
   * @param array $list
   *   An optional list of subdomains to apply instead of the default set.
   * @param int $count
   *   The number of domains to create.
   * @param string|null $base_hostname
   *   The root domain to use for domain creation (e.g. example.com). You should
   *   normally leave this blank to account for alternate test environments.
   */
  public function domainCreateTestDomains(array $list, $count = 1, $base_hostname = NULL) {
    if (empty($base_hostname)) {
      $base_hostname = $this->baseHostname;
    }
    for ($i = 0; $i < $count; $i++) {
      if ($i === 0) {
        $hostname = $base_hostname;
        $machine_name = 'example.com';
        $name = 'Example';
      }
      elseif (!empty($list[$i])) {
        $hostname = $list[$i]['subdomain'] . '.' . $base_hostname;
        $machine_name = $list[$i]['id'];
        $name = $list[$i]['name'];
      }
      // These domains are not setup and are just for UX testing.
      else {
        $hostname = 'test' . $i . '.' . $base_hostname;
        $machine_name = 'test' . $i . '.example.com';
        $name = 'Test ' . $i;
      }
      // Create a new domain programmatically.
      $values = [
        'hostname' => $hostname,
        'name' => $name,
        'id' => \Drupal::entityTypeManager()->getStorage('domain')->createMachineName($machine_name),
      ];
      if (!empty($list[$i]['third_party_settings'])) {
        $values['third_party_settings'] = $list[$i]['third_party_settings'];
      }
      $domain = \Drupal::entityTypeManager()->getStorage('domain')->create($values);
      $domain->save();
    }
  }

}
