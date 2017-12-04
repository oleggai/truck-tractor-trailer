<?php

namespace ListingBundle\Security;

use ListingBundle\Entity\Listing;
use ProfileBundle\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class ListingVoter extends Voter
{

    const EDIT = 'edit';
    const VIEW = 'view';
    const CREATE = 'create';
    const DELETE = 'delete';

    /**
     * @param string $attribute
     * @param mixed $subject
     * @return bool
     */
    protected function supports($attribute, $subject)
    {
        if (!in_array($attribute, array(self::VIEW, self::EDIT, self::CREATE, self::DELETE))) {
            return false;
        }

        if (!$subject instanceof Listing) {
            return false;
        }

        return true;
    }

    /**
     * @param string $attribute
     * @param mixed $subject
     * @param TokenInterface $token
     * @return bool
     */
    protected function voteOnAttribute($attribute, $subject, TokenInterface $token)
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            // the user must be logged in; if not, deny access
            return false;
        }
        /** @var $listing Listing */
        $listing = $subject;

        switch ($attribute) {
            case self::VIEW:
                return $this->canView($listing, $user);
            case self::EDIT:
                return $this->canEdit($listing, $user);
            case self::CREATE:
                return $this->canCreate($listing, $user);
            case self::DELETE:
                return $this->canDelete($listing, $user);
        }

        throw new \LogicException('This code should not be reached!');

    }

    /**
     * @param Listing $listing
     * @param User $user
     */
    private function canView(Listing $listing, User $user)
    {

    }

    /**
     * @param Listing $listing
     * @param User $user
     * @return bool
     */
    private function canEdit(Listing $listing, User $user)
    {

        if (!$listing->getId()) {
            return true;
        }
        return $user === $listing->getUser();
    }

    private function canDelete(Listing $listing, User $user)
    {
        return $user === $listing->getUser();
    }

    /**
     * @param Listing $listing
     * @param User $user
     * @return bool
     */
    private function canCreate(Listing $listing, User $user)
    {
        return true;
    }
}