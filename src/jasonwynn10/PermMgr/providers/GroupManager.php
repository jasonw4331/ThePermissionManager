<?php
namespace jasonwynn10\PermMgr\providers;

use jasonwynn10\PermMgr\ThePermissionManager;

use pocketmine\utils\Config;

class GroupManager {
	/** @var ThePermissionManager $plugin */
	protected $plugin;

	/** @var Config $config */
	protected $config;

	/** @var string $defaultGroup */
	protected $defaultGroup;

	/** @var array $groupAliases */
	protected $groupAliases = [];

	/**
	 * GroupManager constructor.
	 *
	 * @param ThePermissionManager $plugin
	 */
	public function __construct(ThePermissionManager $plugin) {
		$this->plugin = $plugin;
		$plugin->saveResource("groups.yml");
		$config = new Config($plugin->getDataFolder()."groups.yml", Config::YAML);
		$groups = $config->getAll();
		foreach($groups as $group => $data) {
			if(isset($data["isDefault"]) and is_bool($data["isDefault"])) {
				if($data["isDefault"] === true) {
					$this->defaultGroup = $group;
				}
			}else{
				$data["isDefault"] = false;
			}
			if(!isset($data["alias"]) or !is_string($data["alias"])) {
				$data["alias"] = '';
			}else{
				$this->groupAliases[$group] = $data["alias"];
			}
			if(!isset($data["inheritance"]) or !is_array($data["inheritance"])) {
				$data["inheritance"] = [];
			}
			if(!isset($data["permissions"]) or !is_array($data["permissions"])) {
				$data["permissions"] = [];
			}else{
				sort($data["permissions"], SORT_NATURAL | SORT_FLAG_CASE);
			}
			if($this->plugin->getConfig()->get("enable-multiworld-perms", false) === true) {
				if(!isset($data["worlds"]) or !is_array($data["worlds"])) {
					$data["worlds"] = [];
				}
			}
			$config->set($group, $data);
			$this->sortGroupPermissions($group);
		}
		$config->save();
		foreach($groups as $group => $data) {
			foreach($data["alias"] as $key => $alias) {
				if($this->plugin->isAlias($alias)) {
					$data["alias"][$key] = $alias;
				}
			}
			$config->set($group, $data);
		}
		$config->save();
		$this->config = $config;
	}

	/**
	 * @return Config
	 */
	public function getGroupsConfig() : Config {
		$this->config->reload();
		return $this->config;
	}

	/**
	 * @return string
	 */
	public function getDefaultGroup() : string {
		return $this->defaultGroup;
	}

	/**
	 * @param string $group
	 *
	 * @return string[]
	 */
	public function getGroupPermissions(string $group) : array {
		$groupData = $this->config->get($group);
		return $groupData["permissions"];
	}

	/**
	 * @param string $group
	 *
	 * @return string[]
	 */
	public function getAllGroupPermissions(string $group) : array {
		$groupPerms = array_merge($this->getGroupPermissions($group), $this->getInheritedPermissions($group));
		sort($groupPerms, SORT_NATURAL | SORT_FLAG_CASE);
		return array_unique($groupPerms);
	}

	/**
	 * @param string $group
	 * @param string[] $permissions
	 *
	 * @return bool
	 */
	public function setGroupPermissions(string $group, array $permissions) : bool {
		$groupData = $this->config->get($group);
		$groupData["permissions"] = $permissions;
		$this->config->set($group, $groupData);
		return $this->config->save();
	}

	/**
	 * @param string $group
	 * @param string[] $permissions
	 *
	 * @return bool
	 */
	public function addGroupPermissions(string $group, array $permissions = []) : bool {
		$permissions = array_merge($permissions, $this->getGroupPermissions($group));
		return $this->setGroupPermissions($group, $permissions);
	}

	/**
	 * @param string $group
	 * @param string[] $permissions
	 *
	 * @return bool
	 */
	public function removeGroupPermissions(string $group, array $permissions = []) : bool {
		$perms = $this->getGroupPermissions($group);
		foreach($permissions as $permission) {
			if(($key = array_search($permission, $this->getGroupPermissions($group))) !== false) {
				unset($perms[$key]);
			}
		}
		return $this->setGroupPermissions($group, $perms);
	}

	/**
	 * @param string $group
	 *
	 * @return bool
	 */
	public function sortGroupPermissions(string $group) : bool {
		$permissions = $this->getGroupPermissions($group);
		$permissions = array_unique($permissions);
		sort($permissions, SORT_NATURAL | SORT_FLAG_CASE);
		$this->setGroupPermissions($group, $permissions);
		return true;
	}

	/**
	 * @param string $group
	 *
	 * @return string[] includes set and unset permissions from config
	 */
	public function getInheritedPermissions(string $group) : array {
		$this->config->reload();
		$permissions = [];
		foreach($this->config->getAll()[$group]["inheritance"] as $parentGroup) {
			$this->plugin->isAlias($parentGroup); //fixes alias to be real name
			$parentGroupData = $this->config->getAll()[$parentGroup];
			foreach($parentGroupData["permissions"] as $parentPermission) {
				$permissions[] = $parentPermission;
			}
		}
		if(($key = array_search("-*", $permissions)) !== false) {
			unset($permissions[$key]);
			foreach($this->plugin->getServer()->getPluginManager()->getPermissions() as $permission) {
				$permissions[] = "-".$permission->getName();
			}
		}
		if(($key = array_search("*", $permissions)) !== false) {
			unset($permissions[$key]);
			foreach($this->plugin->getServer()->getPluginManager()->getPermissions() as $permission) {
				$permissions[] = $permission->getName();
			}
		}
		return array_unique($permissions, SORT_STRING);
	}

	/**
	 * @return string[]
	 */
	public function getAliases() : array {
		return $this->groupAliases;
	}
	/**
	 * @param string[] $groups
	 *
	 * @return bool
	 */
	public function reloadGroupPermissions(array $groups = []) : bool {
		$this->config->reload();
		if(!empty($groups)) {
			foreach($groups as $group) {
				$data = $this->config->get($group, []);
				sort($data["permissions"], SORT_NATURAL | SORT_FLAG_CASE);
				$this->config->set($group, $data);
				$this->config->save();
				$this->sortGroupPermissions($group);
			}
		}else{
			$groups = $this->config->getAll();
			foreach($groups as $group => $data) {
				sort($data["permissions"], SORT_NATURAL | SORT_FLAG_CASE);
				$this->config->set($group, $data);
				$this->config->save();
				$this->sortGroupPermissions($group);
			}
		}
		return true;
	}
}