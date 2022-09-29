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

/**
 * Command handling related classes
 */
namespace pocketmine\command;

use pocketmine\command\utils\CommandException;
use pocketmine\lang\KnownTranslationFactory;
use pocketmine\lang\Translatable;
use pocketmine\network\mcpe\protocol\AvailableCommandsPacket;
use pocketmine\network\mcpe\protocol\types\command\CommandData;
use pocketmine\network\mcpe\protocol\types\command\CommandParameter;
use pocketmine\permission\PermissionManager;
use pocketmine\Server;
use pocketmine\timings\Timings;
use pocketmine\timings\TimingsHandler;
use pocketmine\utils\BroadcastLoggerForwarder;
use pocketmine\utils\TextFormat;
use function explode;
use function str_replace;

abstract class Command{

	private string $name;

	private string $nextLabel;
	private string $label;

	/** @var string[] */
	private array $aliases = [];

	/** @var string[] */
	private array $activeAliases = [];

	private ?CommandMap $commandMap = null;

	protected Translatable|string $description = "";

	protected Translatable|string $usageMessage;

	private ?string $permission = null;
	private ?string $permissionMessage = null;

	public ?TimingsHandler $timings = null;
	
	/** @var CommandParameter[] */
	private array $overloads = [];

	/**
	 * @param string[] $aliases
	 */
	public function __construct(string $name, Translatable|string $description = "", Translatable|string|null $usageMessage = null, array $aliases = [], array $overloads = null){
		$this->name = $name;
		$this->setLabel($name);
		$this->setDescription($description);
		$this->usageMessage = $usageMessage ?? ("/" . $name);
		$this->setAliases($aliases);
		$this->overloads = (!empty($overloads)) ? $overloads : [[CommandParameter::standard("args", AvailableCommandsPacket::ARG_TYPE_RAWTEXT, 0, true)]];
	}

	/**
	 * @param string[] $args
	 *
	 * @return mixed
	 * @throws CommandException
	 */
	abstract public function execute(CommandSender $sender, string $commandLabel, array $args);

	public function getName() : string{
		return $this->name;
	}

	public function getPermission() : ?string{
		return $this->permission;
	}

	public function setPermission(?string $permission) : void{
		if($permission !== null){
			foreach(explode(";", $permission) as $perm){
				if(PermissionManager::getInstance()->getPermission($perm) === null){
					throw new \InvalidArgumentException("Cannot use non-existing permission \"$perm\"");
				}
			}
		}
		$this->permission = $permission;
	}

	public function testPermission(CommandSender $target, ?string $permission = null) : bool{
		if($this->testPermissionSilent($target, $permission)){
			return true;
		}

		if($this->permissionMessage === null){
			$target->sendMessage(KnownTranslationFactory::pocketmine_command_error_permission($this->name)->prefix(TextFormat::RED));
		}elseif($this->permissionMessage !== ""){
			$target->sendMessage(str_replace("<permission>", $permission ?? $this->permission, $this->permissionMessage));
		}

		return false;
	}

	public function testPermissionSilent(CommandSender $target, ?string $permission = null) : bool{
		$permission ??= $this->permission;
		if($permission === null || $permission === ""){
			return true;
		}

		foreach(explode(";", $permission) as $p){
			if($target->hasPermission($p)){
				return true;
			}
		}

		return false;
	}

	public function getLabel() : string{
		return $this->label;
	}

	public function setLabel(string $name) : bool{
		$this->nextLabel = $name;
		if(!$this->isRegistered()){
			$this->timings = new TimingsHandler(Timings::INCLUDED_BY_OTHER_TIMINGS_PREFIX . "Command: " . $name);
			$this->label = $name;

			return true;
		}

		return false;
	}

	/**
	 * Registers the command into a Command map
	 */
	public function register(CommandMap $commandMap) : bool{
		if($this->allowChangesFrom($commandMap)){
			$this->commandMap = $commandMap;

			return true;
		}

		return false;
	}

	public function unregister(CommandMap $commandMap) : bool{
		if($this->allowChangesFrom($commandMap)){
			$this->commandMap = null;
			$this->activeAliases = $this->aliases;
			$this->label = $this->nextLabel;

			return true;
		}

		return false;
	}

	private function allowChangesFrom(CommandMap $commandMap) : bool{
		return $this->commandMap === null || $this->commandMap === $commandMap;
	}

	public function isRegistered() : bool{
		return $this->commandMap !== null;
	}

	/**
	 * @return string[]
	 */
	public function getAliases() : array{
		return $this->activeAliases;
	}

	public function getPermissionMessage() : ?string{
		return $this->permissionMessage;
	}

	public function getDescription() : Translatable|string{
		return $this->description;
	}

	public function getUsage() : Translatable|string{
		return $this->usageMessage;
	}

	/**
	 * @param string[] $aliases
	 */
	public function setAliases(array $aliases) : void{
		$this->aliases = $aliases;
		if(!$this->isRegistered()){
			$this->activeAliases = $aliases;
		}
	}

	public function setDescription(Translatable|string $description) : void{
		$this->description = $description;
	}

	public function setPermissionMessage(string $permissionMessage) : void{
		$this->permissionMessage = $permissionMessage;
	}

	public function setUsage(Translatable|string $usage) : void{
		$this->usageMessage = $usage;
	}

	/**
	 * @param CommandParameter $parameter
	 * @param int              $overloadIndex
	 */
	public function addParameter(CommandParameter $parameter, int $overloadIndex = 0) : void{
		$this->commandData->overloads[$overloadIndex][] = $parameter;
	}

	/**
	 * @param CommandParameter $parameter
	 * @param int              $parameterIndex
	 * @param int              $overloadIndex
	 */
	public function setParameter(CommandParameter $parameter, int $parameterIndex, int $overloadIndex = 0) : void{
		$this->overloads[$overloadIndex][$parameterIndex] = $parameter;
	}

	/**
	 * @param CommandParameter[] $parameters
	 * @param int                $overloadIndex
	 */
	public function setParameters(array $parameters, int $overloadIndex = 0) : void{
		$this->overloads[$overloadIndex] = array_values($parameters);
	}

	/**
	 * @param int $parameterIndex
	 * @param int $overloadIndex
	 */
	public function removeParameter(int $parameterIndex, int $overloadIndex = 0) : void{
		unset($this->overloads[$overloadIndex][$parameterIndex]);
	}

	public function removeAllParameters() : void{
		$this->overloads = [];
	}

	public function removeOverload(int $overloadIndex) : void{
		unset($this->overloads[$overloadIndex]);
	}

	/**
	 * @param int $index
	 *
	 * @return CommandParameter[]|null
	 */
	public function getOverload(int $index) : ?array{
		return $this->overloads[$index] ?? null;
	}

	/**
	 * @return CommandParameter[][]
	 */
	public function getOverloads() : array{
		return $this->overloads;
	}

	public static function broadcastCommandMessage(CommandSender $source, Translatable|string $message, bool $sendToSource = true) : void{
		$users = $source->getServer()->getBroadcastChannelSubscribers(Server::BROADCAST_CHANNEL_ADMINISTRATIVE);
		$result = KnownTranslationFactory::chat_type_admin($source->getName(), $message);
		$colored = $result->prefix(TextFormat::GRAY . TextFormat::ITALIC);

		if($sendToSource){
			$source->sendMessage($message);
		}

		foreach($users as $user){
			if($user instanceof BroadcastLoggerForwarder){
				$user->sendMessage($result);
			}elseif($user !== $source){
				$user->sendMessage($colored);
			}
		}
	}

	public function __toString() : string{
		return $this->name;
	}
}
