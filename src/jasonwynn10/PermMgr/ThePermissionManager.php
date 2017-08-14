<?php
namespace jasonwynn10\PermMgr;

use jasonwynn10\PermMgr\commands\ListGroupPermissions;
use jasonwynn10\PermMgr\commands\ListUserPermissions;
use jasonwynn10\PermMgr\commands\PluginPermissions;
use jasonwynn10\PermMgr\commands\ReloadPermissions;
use jasonwynn10\PermMgr\commands\SetGroup;
use jasonwynn10\PermMgr\commands\SetGroupPermission;
use jasonwynn10\PermMgr\commands\SetUserPermission;
use jasonwynn10\PermMgr\commands\UnsetGroupPermission;
use jasonwynn10\PermMgr\commands\UnsetUserPermission;
use jasonwynn10\PermMgr\event\EventListener;
use jasonwynn10\PermMgr\event\PermissionAddEvent;
use jasonwynn10\PermMgr\event\PermissionAttachEvent;
use jasonwynn10\PermMgr\event\PermissionDetachEvent;
use jasonwynn10\PermMgr\event\PermissionRemoveEvent;
use jasonwynn10\PermMgr\providers\DataProvider;
use jasonwynn10\PermMgr\providers\GroupManager;
use jasonwynn10\PermMgr\providers\MySQLProvider;
use jasonwynn10\PermMgr\providers\PurePermsProvider;
use jasonwynn10\PermMgr\providers\YAMLProvider;

use pocketmine\lang\BaseLang;
use pocketmine\permission\DefaultPermissions;
use pocketmine\permission\Permission;
use pocketmine\permission\PermissionAttachment;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;

use spoondetector\SpoonDetector;

class ThePermissionManager extends PluginBase {
	/** @var PermissionAttachment[] $perms */
	public $perms = [];

	/** @var DataProvider $playerProvider */
	private $playerProvider;

	/** @var GroupManager $groupProvider */
	private $groupProvider;

	/** @var string[] $superAdmins */
	private $superAdmins = [];

	/** @var BaseLang $baseLang */
	private $baseLang = null;

	public function onLoad() {
		SpoonDetector::printSpoon($this,"spoon.txt");
		$this->saveDefaultConfig();
		$this->getConfig()->reload();
		$this->groupProvider = new GroupManager($this);
		$lang = $this->getConfig()->get("lang", BaseLang::FALLBACK_LANGUAGE);
		$this->baseLang = new BaseLang($lang,$this->getFile() . "resources/");
		switch(strtolower($this->getConfig()->get("data-provider", "yaml"))) {
			case "mysql":
				$this->playerProvider = new MySQLProvider($this);
			break;
			case "pureperms":
				$this->playerProvider = new PurePermsProvider($this);
			break;
			case "yaml":
			default:
				$this->playerProvider = new YAMLProvider($this);
		}

		$this->superAdmins = $this->getConfig()->get("superadmin-groups", []);
	}

	public function onEnable() {
		new EventListener($this);

		$this->getServer()->getCommandMap()->registerAll(self::class, [
			new SetUserPermission($this),
			new UnsetUserPermission($this),
			new ListUserPermissions($this),
			new SetGroupPermission($this),
			new UnsetGroupPermission($this),
			new ListGroupPermissions($this),
			new ReloadPermissions($this),
			new PluginPermissions($this),
			new SetGroup($this)
		]);
	}

	public function onDisable() {
		$permissions = [];
		foreach($this->getServer()->getPluginManager()->getPermissions() as $permission) {
			$permissions[] = $permission->getName();
		}
		$permissions = implode(PHP_EOL, $permissions);
		@file_put_contents($this->getDataFolder()."Permission_List.txt", $permissions);
		foreach($this->getServer()->getOnlinePlayers() as $player) {
			$this->detachPlayer($player);
		}
	}

	public function getLanguage() : BaseLang {
		return $this->baseLang;
	}

	public function getGroups() : GroupManager {
		return $this->groupProvider;
	}

	public function getPlayerProvider() {
		return $this->playerProvider;
	}

	# API

	/**
	 * @param Player $player
	 *
	 * @return bool
	 */
	public function attachPlayer(Player $player) : bool {
		if($this->isAttached($player)) {
			return false;
		}
		$attachment = $player->addAttachment($this);
		$this->perms[$player->getId()] = $attachment;
		$this->getServer()->getPluginManager()->callEvent(new PermissionAttachEvent($this));
		$this->playerProvider->init($player);
		$groupPerms = $this->getGroups()->getAllGroupPermissions($this->getPlayerProvider()->getGroup($player));
		foreach($groupPerms as $permission) {
			if($this->sortPermissionConfigStrings($permission)) {
				$this->addPlayerPermission($player, new Permission($permission), true);
			}else{
				$this->removePlayerPermission($player, new Permission($permission), true);
			}
		}
		$this->playerProvider->sortPlayerPermissions($player);
		$playerPerms = $this->playerProvider->getPlayerPermissions($player);
		foreach($playerPerms as $permission) {
			if($this->sortPermissionConfigStrings($permission)) {
				$this->removePlayerPermission($player, new Permission($permission), false);
			}else{
				$this->addPlayerPermission($player, new Permission($permission), false);
			}
		}
		return true;
	}

	/**
	 * @param Player $player
	 *
	 * @return bool
	 */
	public function detachPlayer(Player $player) : bool {
		$this->getServer()->getPluginManager()->callEvent(new PermissionDetachEvent($this));
		$player->removeAttachment($this->perms[$player->getId()]);
		unset($this->perms[$player->getId()]);
		return true;
	}

	/**
	 * @param Player $player
	 *
	 * @return bool
	 */
	public function isAttached(Player $player) : bool {
		return isset($this->perms[$player->getId()]);
	}

	/**
	 * @param Player $player
	 * @param Permission $permission
	 * @param bool $group
	 *
	 * @return bool
	 */
	public function addPlayerPermission(Player $player, Permission $permission, bool $group = false) : bool {
		if(!$this->isAttached($player)) {
			return false;
		}
		$ev = new PermissionAddEvent($this, $permission, $group);
		if(strtolower($permission->getName()) === "pocketmine.command.op" and $this->getConfig()->get("disable-op", true) !== true) {
			$ev->setCancelled();
			$this->removePlayerPermission($player, $permission, $ev->isGroup());
		}
		$this->getServer()->getPluginManager()->callEvent($ev);
		if($ev->isCancelled()) {
			return false;
		}

		$attachment = $this->perms[$player->getId()];
		$attachment->setPermission($ev->getPermission(), true);

		if(!$ev->isGroup()) {
			$this->playerProvider->setPlayerPermissions($player, [$permission->getName()]);
			$this->playerProvider->sortPlayerPermissions($player);
		}
		return true;
	}

	/**
	 * @param Player $player
	 * @param Permission $permission
	 * @param bool $group
	 *
	 * @return bool
	 */
	public function removePlayerPermission(Player $player, Permission $permission, bool $group = false) : bool {
		if(!$this->isAttached($player)) {
			return false;
		}
		$this->getServer()->getPluginManager()->callEvent($ev = new PermissionRemoveEvent($this, $permission, $group));
		if($ev->isCancelled() and !(strtolower($permission->getName()) === "pocketmine.command.op" and $this->getConfig()->get("disable-op", true))) {
			return false;
		}

		$attachment = $this->perms[$player->getId()];
		$attachment->setPermission($ev->getPermission(), false);

		if(!$ev->isGroup()) {
			$this->getPlayerProvider()->removePlayerPermissions($player, [$permission->getName()]);
		}
		return true;
	}

	/**
	 * @param string $group
	 * @param Permission $permission
	 *
	 * @return bool
	 */
	public function addGroupPermission(string $group, Permission $permission) : bool {
		$ev = new PermissionAddEvent($this, $permission);
		if(strtolower($permission->getName()) === "pocketmine.command.op" and $this->getConfig()->get("disable-op", true) !== true) {
			$ev->setCancelled();
		}
		$this->getServer()->getPluginManager()->callEvent($ev);
		if($ev->isCancelled()) {
			return false;
		}
		$this->groupProvider->addGroupPermissions($group, [$permission->getName()]);
		$this->groupProvider->sortGroupPermissions($group);
		$this->reloadGroupPermissions();
		$this->reloadPlayerPermissions();
		return true;
	}

	/**
	 * @param string $group
	 * @param Permission $permission
	 *
	 * @return bool
	 */
	public function removeGroupPermission(string $group, Permission $permission) : bool {
		$this->getServer()->getPluginManager()->callEvent($ev = new PermissionRemoveEvent($this, $permission));
		if($ev->isCancelled() and !(strtolower($permission->getName()) === "pocketmine.command.op" and $this->getConfig()->get("disable-op", true))) {
			return false;
		}

		$this->groupProvider->removeGroupPermissions($group, [$permission->getName()]);
		$this->groupProvider->sortGroupPermissions($group);
		$this->reloadGroupPermissions();
		$this->reloadPlayerPermissions();
		return true;
	}


	/**
	 * @param Player[] $players
	 *
	 * @return bool
	 */
	public function reloadPlayerPermissions(array $players = []) : bool {
		$players = !empty($players) ? $players : $this->getServer()->getOnlinePlayers();
		foreach($players as $player) {
			if(!$this->isAttached($player)) {
				continue;
			}
			$player->removeAttachment($this->perms[$player->getId()]);
			unset($this->perms[$player->getId()]);
			$attachment = $player->addAttachment($this);
			$this->perms[$player->getId()] = $attachment;

			foreach($this->getPlayerProvider()->getPlayerPermissions($player) as $permission) {
				if($this->sortPermissionConfigStrings($permission)) {
					$this->addPlayerPermission($player, new Permission($permission), false);
				}else{
					$this->removePlayerPermission($player, new Permission($permission), false);
				}
			}
			foreach($this->getPlayerProvider()->getAllPlayerPermissions($player) as $permission) {
				if($this->sortPermissionConfigStrings($permission)) {
					$this->addPlayerPermission($player, new Permission($permission), true);
				}else{
					$this->removePlayerPermission($player, new Permission($permission), true);
				}
			}
		}
		return true;
	}

	/**
	 * @param string[] $groups
	 *
	 * @return bool
	 */
	public function reloadGroupPermissions(array $groups = []) : bool {
		return $this->groupProvider->reloadGroupPermissions($groups);
	}

	/**
	 * @param string $permission
	 *
	 * @return bool returns true on positive given permission and negative on taken permission
	 */
	public function sortPermissionConfigStrings(string &$permission) : bool {
		if($permission{0} === "-") {
			$permission = str_replace("-", "", $permission);
			return false;
		}else{
			return true;
		}
	}

	/**
	 * @param string &$group returns the full group name
	 *
	 * @return bool
	 */
	public function isAlias(string &$group) : bool {
		if(in_array($group, $this->getGroups()->getAliases())) {
			$group = array_search($group, $this->getGroups()->getAliases());
			return true;
		}
		return false;
	}

	/**
	 * @param Player $player
	 * @param string $group
	 *
	 * @return bool
	 */
	public function setPlayerGroup(Player $player, string $group) : bool {
		$config = new Config($this->getDataFolder()."players".DIRECTORY_SEPARATOR.$player->getLowerCaseName().DIRECTORY_SEPARATOR."permissions.yml", Config::YAML);
		$config->set("group", $group);
		$this->reloadPlayerPermissions([$player]);
		return $config->save();
	}

	/**
	 * @return string[]
	 */
	public function getSuperAdmins() : array {
		return $this->superAdmins;
	}

	/**
	 * @return Permission[]
	 */
	public function getPocketMinePermissions() : array {
		$pmPerms = [];
		/** @var Permission $permission */
		foreach($this->getServer()->getPluginManager()->getPermissions() as $permission) {
			if(strpos($permission->getName(), DefaultPermissions::ROOT) !== false) {
				$pmPerms[] = $permission;
			}
		}
		return $pmPerms;
	}
}