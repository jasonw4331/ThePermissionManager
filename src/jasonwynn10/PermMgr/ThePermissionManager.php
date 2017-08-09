<?php
namespace jasonwynn10\PermMgr;

use jasonwynn10\PermMgr\event\EventListener;
use jasonwynn10\PermMgr\event\PermissionAddEvent;
use jasonwynn10\PermMgr\event\PermissionRemoveEvent;

use jasonwynn10\PermMgr\task\PermissionExpirationTask;
use pocketmine\lang\BaseLang;
use pocketmine\permission\Permission;
use pocketmine\permission\PermissionAttachment;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;

class ThePermissionManager extends PluginBase {
	/** @var PermissionAttachment[] $perms */
	public $perms = [];

	/** @var BaseLang $baseLang */
	private $baseLang = null;

	public function onLoad() {
		$this->saveDefaultConfig();
		$this->saveResource("groups.yml");
		$lang = $this->getConfig()->get("Lang", BaseLang::FALLBACK_LANGUAGE);
		$this->baseLang = new BaseLang($lang,$this->getFile() . "resources/");
	}

	public function onEnable() {
		new EventListener($this);
		$this->getServer()->getScheduler()->scheduleRepeatingTask(new PermissionExpirationTask($this), 20);
	}

	public function onDisable() {
		foreach($this->getServer()->getOnlinePlayers() as $player){
			$this->detachPlayer($player);
		}
	}

	public function getLanguage() : BaseLang {
		return $this->baseLang;
	}

	# API

	/**
	 * @param Player $player
	 *
	 * @return bool
	 */
	public function attachPlayer(Player $player) : bool {
		if(isset($this->perms[$player->getId()])) {
			return false;
		}
		$attachment = $player->addAttachment($this);
		$config = new Config($this->getDataFolder()."players".DIRECTORY_SEPARATOR.$player->getLowerCaseName().DIRECTORY_SEPARATOR."permissions.yml", Config::YAML);
		foreach($config->getAll() as $permission => $bool) {
			$attachment->setPermission($permission, $bool);
		}
		$this->perms[$player->getId()] = $attachment;
		return true;
	}

	/**
	 * @param Player $player
	 *
	 * @return bool
	 */
	public function detachPlayer(Player $player) : bool {
		$config = new Config($this->getDataFolder()."players".DIRECTORY_SEPARATOR.$player->getLowerCaseName().DIRECTORY_SEPARATOR."permissions.yml", Config::YAML);
		$config->setAll($this->perms[$player->getId()]->getPermissions());
		$player->removeAttachment($this->perms[$player->getId()]);
		unset($this->perms[$player->getId()]);
		return true;
	}

	/**
	 * @param Player $player
	 * @param Permission $permission
	 *
	 * @return bool
	 */
	public function addPlayerPermission(Player $player, Permission $permission) : bool {
		$this->getServer()->getPluginManager()->callEvent($ev = new PermissionAddEvent($this, $permission));
		if($ev->isCancelled()) {
			return false;
		}

		if(isset($this->perms[$player->getId()])) {
			$attachment = $this->perms[$player->getId()];
		}else{
			$this->perms[$player->getId()] = $player->addAttachment($this, $ev->getPermission()->getName(), true);
			return true;
		}
		$attachment->setPermission($ev->getPermission(), true);
		return true;
	}

	/**
	 * @param Player $player
	 * @param Permission $permission
	 *
	 * @return bool
	 */
	public function RemovePlayerPermission(Player $player, Permission $permission) : bool {
		$this->getServer()->getPluginManager()->callEvent($ev = new PermissionRemoveEvent($this, $permission));
		if($ev->isCancelled()) {
			return false;
		}

		$attachment = $this->perms[$player->getId()];
		$attachment->setPermission($ev->getPermission(), false);
		return true;
	}

	public function reloadPlayerPermissions() {
		foreach($this->getServer()->getOnlinePlayers() as $player) {
			$attachment = $player->addAttachment($this);
			$config = new Config($this->getDataFolder()."players".DIRECTORY_SEPARATOR.$player->getLowerCaseName().DIRECTORY_SEPARATOR."permissions.yml", Config::YAML);
			//$config->reload();
			foreach($config->getAll() as $permission => $bool) {
				$attachment->setPermission($permission, $bool);
			}
			$this->perms[$player->getId()] = $attachment;
		}
	}
}