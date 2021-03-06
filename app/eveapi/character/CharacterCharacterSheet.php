<?php

namespace Seat\EveApi\Character;

use Seat\EveApi\BaseApi;
use Pheal\Pheal;

class CharacterSheet extends BaseApi {

	public static function Update($keyID, $vCode)
	{

		// Start and validate they key pair
		BaseApi::bootstrap();
		BaseApi::validateKeyPair($keyID, $vCode);

		// Set key scopes and check if the call is banned
		$scope = 'Char';
		$api = 'CharacterSheet';

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

		// Next, start our loop over the characters and upate the database
		foreach ($characters as $characterID) {

			// Prepare the Pheal instance
			$pheal = new Pheal($keyID, $vCode);

			// Do the actual API call. pheal-ng actually handles some internal
			// caching too.
			try {
				
				$character_sheet = $pheal
					->charScope
					->CharacterSheet(array('characterID' => $characterID));

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
			if (!BaseApi::checkDbCache($scope, $api, $character_sheet->cached_until, $characterID)) {

				$character_data = \EveCharacterCharacterSheet::where('characterID', '=', $characterID)->first();

				if (!$character_data)
					$new_data = new \EveCharacterCharacterSheet;
				else
					$new_data = $character_data;

				$new_data->characterID = $character_sheet->characterID;
				$new_data->name = $character_sheet->name;
				$new_data->DoB = $character_sheet->DoB;
				$new_data->race = $character_sheet->race;
				$new_data->bloodLine = $character_sheet->bloodLine;
				$new_data->ancestry = $character_sheet->ancestry;
				$new_data->gender = $character_sheet->gender;
				$new_data->corporationName = $character_sheet->corporationName;
				$new_data->corporationID = $character_sheet->corporationID;
				$new_data->cloneName = $character_sheet->cloneName;
				$new_data->cloneSkillPoints = $character_sheet->cloneSkillPoints;
				$new_data->balance = $character_sheet->balance;
				$new_data->intelligence = $character_sheet->attributes->intelligence;
				if(isset($character_sheet->attributeEnhancers->intelligenceBonus)){
					$new_data->intelligenceAugmentatorName = $character_sheet->attributeEnhancers->intelligenceBonus->augmentatorName;
					$new_data->intelligenceAugmentatorValue = $character_sheet->attributeEnhancers->intelligenceBonus->augmentatorValue;
				} else {
					$new_data->intelligenceAugmentatorName = null;
					$new_data->intelligenceAugmentatorValue = null;
				}

				$new_data->memory = $character_sheet->attributes->memory;
				if(isset($character_sheet->attributeEnhancers->memoryBonus)){
					$new_data->memoryAugmentatorName = $character_sheet->attributeEnhancers->memoryBonus->augmentatorName;
					$new_data->memoryAugmentatorValue = $character_sheet->attributeEnhancers->memoryBonus->augmentatorValue;
				} else {
					$new_data->memoryAugmentatorName = null;
					$new_data->memoryAugmentatorValue = null;
				}

				$new_data->charisma = $character_sheet->attributes->charisma;
				if(isset($character_sheet->attributeEnhancers->charismaBonus)){
					$new_data->charismaAugmentatorName = $character_sheet->attributeEnhancers->charismaBonus->augmentatorName;
					$new_data->charismaAugmentatorValue = $character_sheet->attributeEnhancers->charismaBonus->augmentatorValue;
				} else {
					$new_data->charismaAugmentatorName = null;
					$new_data->charismaAugmentatorValue = null;
				}

				$new_data->perception = $character_sheet->attributes->perception;
				if(isset($character_sheet->attributeEnhancers->perceptionBonus)){
					$new_data->perceptionAugmentatorName = $character_sheet->attributeEnhancers->perceptionBonus->augmentatorName;
					$new_data->perceptionAugmentatorValue = $character_sheet->attributeEnhancers->perceptionBonus->augmentatorValue;
				} else {
					$new_data->perceptionAugmentatorName = null;
					$new_data->perceptionAugmentatorValue = null;
				}

				$new_data->willpower = $character_sheet->attributes->willpower;
				if(isset($character_sheet->attributeEnhancers->willpowerBonus)){
					$new_data->willpowerAugmentatorName = $character_sheet->attributeEnhancers->willpowerBonus->augmentatorName;
					$new_data->willpowerAugmentatorValue = $character_sheet->attributeEnhancers->willpowerBonus->augmentatorValue;
				} else {
					$new_data->willpowerAugmentatorName = null;
					$new_data->willpowerAugmentatorValue = null;
				}
				$new_data->save();

				// Update the characters skills
				foreach ($character_sheet->skills as $skill) {

					$skill_data = \EveCharacterCharacterSheetSkills::where('characterID', '=', $characterID)
						->where('typeID', '=', $skill->typeID)
						->first();

					if (!$skill_data)
						$skill_info = new \EveCharacterCharacterSheetSkills;
					else
						$skill_info = $skill_data;

					$skill_info->characterID = $characterID;
					$skill_info->typeID = $skill->typeID;
					$skill_info->skillpoints = $skill->skillpoints;
					$skill_info->level = $skill->level;
					$skill_info->published = $skill->published;
					$new_data->skills()->save($skill_info);
				}

				// Update the cached_until time in the database for this api call
				BaseApi::setDbCache($scope, $api, $character_sheet->cached_until, $characterID);
			}
		}

		// Unlock the call
		BaseApi::unlockCall($lockhash);

		return $character_sheet;
	}
}
