<?php

/*
 *
 *  ____            _        _   __  __ _                  __  __ ____
 * |  _ \ ___   ___| | _____| |_|  \/  (_)_ __   ___      |  \/  |  _ \
 * | |_) / _ \ / __| |/ / _ \ __| |\/| | | '_ \ / _ \_____| |\/| | |_) |
 * |  __/ (_) | (__|   <  __/ |_| |  | | | | | |  __/_____| |  | |  __/
 * |_|   \___/ \___|_|\_\___|\__|_|  |_|_|_| |_|\___|     |_|  |_|_|
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author PocketMine Team
 * @link http://www.pocketmine.net/
 *
 *
 */

declare(strict_types=1);

namespace pocketmine\network\mcpe\convert;

use pocketmine\entity\InvalidSkinException;
use pocketmine\entity\Skin;
use pocketmine\network\mcpe\protocol\types\skin\SkinData;
use pocketmine\network\mcpe\protocol\types\skin\SkinImage;
use function is_array;
use function is_string;
use function json_decode;
use function json_encode;
use function random_bytes;
use function str_repeat;
use const JSON_THROW_ON_ERROR;

class LegacySkinAdapter implements SkinAdapter{

	public function toSkinData(Skin $skin) : SkinData{
		$capeData = $skin->getCapeData();
		$capeImage = $capeData === "" ? new SkinImage(0, 0, "") : new SkinImage(32, 64, $capeData);
		return new SkinData(
			$skin->getSkinId(),
			$skin->getPlayFabId(),
			$skin->getResourcePatch(),
			SkinImage::fromLegacy($skin->getSkinData()),
			$skin->getAnimations(),
			$capeImage,
			$skin->getGeometryData(),
			$skin->getGeometryDataEngineVersion(),
			$skin->getAnimationData(),
			$skin->getCapeId(),
			$skin->getFullSkinId(),
			$skin->getArmSize(),
			$skin->getSkinColor(),
			$skin->getPersonaPieces(),
			$skin->getPieceTintColors(),
			$skin->isVerified(),
			$skin->isPremium(),
			$skin->isPersona(),
			$skin->isPersonaCapeOnClassic(),
			$skin->isPrimaryUser()
		);
	}

	public function fromSkinData(SkinData $data) : Skin{
		$resourcePatch = json_decode($data->getResourcePatch(), true);
		if(is_array($resourcePatch) && isset($resourcePatch["geometry"]["default"]) && is_string($resourcePatch["geometry"]["default"])){
			$geometryName = $resourcePatch["geometry"]["default"];
		}else{
			throw new InvalidSkinException("Missing geometry name field");
		}

		$skin = new Skin($data->getSkinId(), $data->getSkinImage()->getData(), $data->getCapeImage()->getData(), $geometryName, $data->getGeometryData());
		$skin->setPlayFabId($data->getPlayFabId());
		$skin->setResourcePatch($data->getResourcePatch());
		$skin->setSkinImage($data->getSkinImage());
		$skin->setAnimations($data->getAnimations());
		$skin->setAnimationData($data->getAnimationData());
		$skin->setCapeId($data->getCapeId());
		$skin->setFullSkinId($data->getSkinId()); //1.19.60 Skin Bug Fix..
		$skin->setArmSize($data->getArmSize());
		$skin->setSkinColor($data->getSkinColor());
		$skin->setPersonaPieces($data->getPersonaPieces());
		$skin->setPieceTintColors($data->getPieceTintColors());
		$skin->setVerified($data->isVerified());
		$skin->setPersona($data->isPersona());
		$skin->setPremium($data->isPremium());
		$skin->setPersonaCapeOnClassic($data->isPersonaCapeOnClassic());
		$skin->setPrimaryUser($data->isPrimaryUser());
		return $skin;
	}
}
