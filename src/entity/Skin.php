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

namespace pocketmine\entity;

use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\network\mcpe\protocol\types\skin\SkinAnimation;
use pocketmine\network\mcpe\protocol\types\skin\SkinData;
use pocketmine\network\mcpe\protocol\types\skin\SkinImage;

use Ahc\Json\Comment as CommentedJsonDecoder;
use function implode;
use function in_array;
use function json_encode;
use function json_last_error_msg;
use function strlen;
use const JSON_THROW_ON_ERROR;

final class Skin{
	public const ACCEPTED_SKIN_SIZES = [
		SkinImage::SINGLE_SKIN_SIZE,
		SkinImage::DOUBLE_SKIN_SIZE,
		SkinImage::SKIN_128_32_SIZE,
		SkinImage::SKIN_128_64_SIZE,
		SkinImage::SKIN_128_128_SIZE,
		SkinImage::SKIN_256_32_SIZE,
		SkinImage::SKIN_256_64_SIZE,
		SkinImage::SKIN_256_128_SIZE,
		SkinImage::SKIN_256_256_SIZE,
		SkinImage::SKIN_512_32_SIZE,
		SkinImage::SKIN_512_64_SIZE,
		SkinImage::SKIN_512_128_SIZE,
		SkinImage::SKIN_512_256_SIZE,
		SkinImage::SKIN_512_512_SIZE
	];

	private string $skinId;
	private string $skinData;
	private string $capeData;
	private string $geometryName;
	private string $geometryData;

	/** @var string */
	private $playFabId = "";
	/** @var string */
	private $resourcePatch;
	/** @var SkinImage */
	private $skinImage;
	/** @var SkinAnimation[] */
	private $animations = [];
	/** @var string */
	private $geometryDataEngineVersion = ProtocolInfo::MINECRAFT_VERSION_NETWORK;
	/** @var string */
	private $animationData = "";
	/** @var string */
	private $capeId = "";
	/** @var string */
	private $fullSkinId = "";
	/** @var string */
	private $armSize = SkinData::ARM_SIZE_WIDE;
	/** @var string */
	private $skinColor = "";
	/** @var PersonaSkinPiece[] */
	private $personaPieces = [];
	/** @var PersonaPieceTintColor[] */
	private $pieceTintColors = [];
	/** @var bool */
	private $isVerified = true;
	/** @var bool */
	private $persona = false;
	/** @var bool */
	private $premium = false;
	/** @var bool */
	private $personaCapeOnClassic = true;
	/** @var bool */
	private $isPrimaryUser = true;

	public function __construct(string $skinId, string $skinData, string $capeData = "", string $geometryName = "", string $geometryData = ""){
		if($skinId === ""){
			throw new InvalidSkinException("Skin ID must not be empty");
		}
		$len = strlen($skinData);
		if(!in_array($len, self::ACCEPTED_SKIN_SIZES, true)){
			throw new InvalidSkinException("Invalid skin data size $len bytes (allowed sizes: " . implode(", ", self::ACCEPTED_SKIN_SIZES) . ")");
		}
		if($capeData !== "" && strlen($capeData) !== SkinImage::SINGLE_SKIN_SIZE){
			throw new InvalidSkinException("Invalid cape data size " . strlen($capeData) . " bytes (must be exactly 8192 bytes)");
		}

		if($geometryData !== ""){
			$decodedGeometry = (new CommentedJsonDecoder())->decode($geometryData);
			if($decodedGeometry === false){
				throw new InvalidSkinException("Invalid geometry data (" . json_last_error_msg() . ")");
			}

			/*
			 * Hack to cut down on network overhead due to skins, by un-pretty-printing geometry JSON.
			 *
			 * Mojang, some stupid reason, send every single model for every single skin in the selected skin-pack.
			 * Not only that, they are pretty-printed.
			 * TODO: find out what model crap can be safely dropped from the packet (unless it gets fixed first)
			 */
			$geometryData = json_encode($decodedGeometry, JSON_THROW_ON_ERROR);
		}

		$this->skinId = $skinId;
		$this->skinData = $skinData;
		$this->capeData = $capeData;
		$this->geometryName = $geometryName;
		$this->geometryData = $geometryData;
	}

	public function getSkinId() : string{
		return $this->skinId;
	}

	public function setSkinId(string $skinId) : self{
		$this->skinId = $skinId;
		return $this;
	}

	public function getSkinData() : string{
		return $this->skinData;
	}

	public function setSkinData(string $skinData) : self{
		$this->skinData = $skinData;
		return $this;
	}

	public function getCapeData() : string{
		return $this->capeData;
	}

	public function setCapeData(string $capeData) : self{
		$this->capeData = $capeData;
		return $this;
	}

	public function getGeometryName() : string{
		return $this->geometryName;
	}

	public function setGeometryName(string $geometryName) : self{
		$this->geometryName = $geometryName;
		return $this;
	}

	public function getGeometryData() : string{
		return $this->geometryData;
	}

	public function setGeometryData(string $geometryData) : self{
		$this->geometryData = $geometryData;
		return $this;
	}


	public function getPlayFabId() : string{
		return $this->playFabId;
	}

	public function setPlayFabId(string $playFabId) : self{
		$this->playFabId = $playFabId;
		return $this;
	}

	public function getResourcePatch() : string{
		return $this->resourcePatch;
	}

	public function setResourcePatch(string $resourcePatch) : self{
		$this->resourcePatch = $resourcePatch;
		return $this;
	}

	public function getSkinImage() : SkinImage{
		return $this->skinImage;
	}

	public function setSkinImage(SkinImage $skinImage) : self{
		$this->skinImage = $skinImage;
		return $this;
	}

	/**
	 * @return SkinAnimation[]
	 */
	public function getAnimations() : array{
		return $this->animations;
	}

	/**
	 * @param SkinAnimation[] $animations
	 */
	public function setAnimations(array $animations) : self{
		$this->animations = $animations;
		return $this;
	}

	public function getCapeImage() : SkinImage{
		return new SkinImage(32, 64, $this->capeData);
	}

	public function getGeometryDataEngineVersion() : string{
		return $this->geometryDataEngineVersion;
	}

	public function getAnimationData() : string{
		return $this->animationData;
	}

	public function setAnimationData(string $animationData) : self{
		$this->animationData = $animationData;
		return $this;
	}

	public function getCapeId() : string{
		return $this->capeId;
	}

	public function setFullSkinId(string $fullSkinId) : self{
		$this->fullSkinId = $fullSkinId;
		return $this;
	}

	public function getFullSkinId() : string{
		return $this->fullSkinId;
	}

	public function setCapeId(string $capeId) : self{
		$this->capeId = $capeId;
		return $this;
	}

	public function getArmSize() : string{
		return $this->armSize;
	}

	public function setArmSize(string $armSize) : self{
		$this->armSize = $armSize;
		return $this;
	}

	public function getSkinColor() : string{
		return $this->skinColor;
	}

	public function setSkinColor(string $skinColor) : self{
		$this->skinColor = $skinColor;
		return $this;
	}

	/**
	 * @return PersonaSkinPiece[]
	 */
	public function getPersonaPieces() : array{
		return $this->personaPieces;
	}

	/**
	 * @param PersonaSkinPiece[] $personaPieces
	 */
	public function setPersonaPieces(array $personaPieces) : self{
		$this->personaPieces = $personaPieces;
		return $this;
	}

	/**
	 * @return PersonaPieceTintColor[]
	 */
	public function getPieceTintColors() : array{
		return $this->pieceTintColors;
	}

	/**
	 * @param PersonaPieceTintColor[] $pieceTintColors
	 */
	public function setPieceTintColors(array $pieceTintColors) : self{
		$this->pieceTintColors = $pieceTintColors;
		return $this;
	}

	public function isVerified() : bool{
		return $this->isVerified;
	}

	public function setVerified(bool $isVerified) : Skin{
		$this->isVerified = $isVerified;
		return $this;
	}

	public function isPersona() : bool{
		return $this->persona;
	}

	public function setPersona(bool $persona) : self{
		$this->persona = $persona;
		return $this;
	}

	public function isPremium() : bool{
		return $this->premium;
	}

	public function setPremium(bool $premium) : self{
		$this->premium = $premium;
		return $this;
	}

	public function isPersonaCapeOnClassic() : bool{
		return $this->personaCapeOnClassic;
	}

	public function setPersonaCapeOnClassic(bool $personaCapeOnClassic) : self{
		$this->personaCapeOnClassic = $personaCapeOnClassic;
		return $this;
	}

	public function isPrimaryUser() : bool{
		return $this->isPrimaryUser;
	}

	public function setPrimaryUser(bool $isPrimaryUser) : self{
		$this->isPrimaryUser = $isPrimaryUser;
		return $this;
	}
}