<?php

namespace Ryssbowh\RestrictDeletion\models;

use Ryssbowh\RestrictDeletion\services\Restrict;
use craft\base\Model;

class Settings extends Model
{
    public array $policies = [];
    public string $userPolicy = '';
    public string $defaultPolicy = Restrict::POLICY_NONE;
    public bool $adminCanOverride = false;
    public bool $disableForFrontRequests = false;
    public bool $disableForConsoleRequests = false;

    /**
     * Get the policy for a uid (section, category group, volume or 'users')
     *
     * @param  string $uid
     * @return string
     */
    public function getPolicy(string $uid): string
    {
        if ($uid == 'users') {
            $policy = $this->userPolicy;
        } else {
            $policy = $this->policies[$uid] ?? null;
        }
        return $policy ?: $this->defaultPolicy;
    }
}
