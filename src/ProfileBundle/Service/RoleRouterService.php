<?php

namespace ProfileBundle\Service;

use ProfileBundle\Entity\User;

class RoleRouterService
{

    public function getLoginRoute(User $user = null)
    {
        $user = $this->ifNull($user);
        if ($user->checkRole(User::ROLE_ADMIN)) {
            return 'AdminBundle_User_list';
        } else if ($user->checkRole(User::ROLE_VENDOR)) {
            return 'vendor_index';
        } else if ($user->checkRole(User::ROLE_PROFILE_USER)) {
            return 'dashboard';
        }

        return 'security_logout';
    }

    public function getFrontAction(User $user = null)
    {
        $user = $this->ifNull($user);
        if ($user->checkRole(User::ROLE_ADMIN)) {
            return ['action' => 'redirect', 'route' => 'AdminBundle_User_list'];
        } else if ($user->checkRole(User::ROLE_VENDOR)) {
            return ['action' => 'redirect', 'route' => 'vendor_index'];
        }

        return ['action' => 'forward', 'controller' => 'WebsiteBundle:Page:home'];
    }

    private function ifNull(User $user = null)
    {
        return empty($user) ? new User() : $user;
    }

}