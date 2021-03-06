<?php

namespace Seat\EveApi\Corporation;

use Seat\EveApi\BaseApi;
use Pheal\Pheal;

class ContactList extends BaseApi {

	public static function Update($keyID, $vCode)
	{

		// Start and validate they key pair
		BaseApi::bootstrap();
		BaseApi::validateKeyPair($keyID, $vCode);

		// Set key scopes and check if the call is banned
		$scope = 'Corp';
		$api = 'ContactList';

		if (BaseApi::isBannedCall($api, $scope, $keyID))
			return;

		// Get the characters for this key
		$characters = BaseApi::findKeyCharacters($keyID);

		// Check if this key has any characters associated with it
		if (!$characters)
			return;

		// Lock the call so that we are the only instance of this running now()
		// If it is already locked, just return without doing anything
		if (!BaseApi::isLockedCall($api, $scope, $keyID))
			$lockhash = BaseApi::lockCall($api, $scope, $keyID);
		else
			return;

		// So I think a corporation key will only ever have one character
		// attached to it. So we will just use that characterID for auth
		// things, but the corporationID for locking etc.
		$corporationID = BaseApi::findCharacterCorporation($characters[0]);

		// Prepare the Pheal instance
		$pheal = new Pheal($keyID, $vCode);

		// Do the actual API call. pheal-ng actually handles some internal
		// caching too.
		try {
			
			$contact_list = $pheal
				->corpScope
				->ContactList(array('characterID' => $characters[0]));

		} catch (\Pheal\Exceptions\APIException $e) {

			// If we cant get account status information, prevent us from calling
			// this API again
			BaseApi::banCall($api, $scope, $keyID, 0, $e->getCode() . ': ' . $e->getMessage());
		    return;

		} catch (\Pheal\Exceptions\PhealException $e) {

			throw $e;
		}

		// Check if the data in the database is still considered up to date.
		// checkDbCache will return true if this is the case
		if (!BaseApi::checkDbCache($scope, $api, $contact_list->cached_until, $corporationID)) {

			// The ContactList API will potentially return the corporations:
			// a) corporation contacts,
			// b) alliance contracts.

			// TODO: Think about maybe doing this in a transaction?

			// First, the corporate contacts
			// Remove the current contacts
			\EveCorporationContactListCorporate::where('corporationID', '=', $corporationID)->delete();

			// Loop over the list we got from the api and update the db
			foreach ($contact_list->corporateContactList as $contact) {
				
				$new_contact = new \EveCorporationContactListCorporate;
				$new_contact->corporationID = $corporationID;
				$new_contact->contactID = $contact->contactID;
				$new_contact->contactName = $contact->contactName;
				$new_contact->standing = $contact->standing;
				$new_contact->contactTypeID = $contact->contactTypeID;
				$new_contact->save();
			}

			// Second, the alliance contacts
			// Remove the current contacts
			\EveCorporationContactListAlliance::where('corporationID', '=', $corporationID)->delete();

			// Loop over the list we got from the api and update the db
			foreach ($contact_list->allianceContactList as $contact) {
				
				$new_contact = new \EveCorporationContactListAlliance;
				$new_contact->corporationID = $corporationID;
				$new_contact->contactID = $contact->contactID;
				$new_contact->contactName = $contact->contactName;
				$new_contact->standing = $contact->standing;
				$new_contact->contactTypeID = $contact->contactTypeID;
				$new_contact->save();
			}

			// Update the cached_until time in the database for this api call
			BaseApi::setDbCache($scope, $api, $contact_list->cached_until, $corporationID);
		}

		// Unlock the call
		BaseApi::unlockCall($lockhash);

		return $contact_list;
	}
}
