<?php
declare(strict_types=1);
namespace jasonwynn10\PermMgr\providers;

use jasonwynn10\PermMgr\ThePermissionManager;

use pocketmine\utils\Config;

class GroupManager {
	/** @var ThePermissionManager $plugin */
	protected $plugin;

	/** @var Config $config */
	protected $config;

	/** @var array $defaultGroups */
	protected $defaultGroups;

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
		$this->config = new Config($plugin->getDataFolder()."groups.yml", Config::YAML);
		$groups = $this->config->getAll();
		foreach($groups as $group => $data) {
			if(isset($data["isDefault"]) and is_bool($data["isDefault"])) {
				if($data["isDefault"] === true) {
					$this->defaultGroups[] = $group;
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
			}else{
				foreach($data["inheritance"] as $key => $alias) {
					if($this->isAlias($alias)) {
						$data["inheritance"][$key] = $alias;
					}
				}
				sort($data["inheritance"], SORT_NATURAL | SORT_FLAG_CASE);
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
			$this->config->set($group, $data);
		}
		$this->config->save(true);
	}

	/**
	 * @return Config
	 */
	public function getGroupsConfig() : Config {
		$this->config->reload();
		return $this->config;
	}

	/**
	 * @return array
	 */
	public function getDefaultGroups() : array {
		return $this->defaultGroups;
	}

	/**
	 * @param string[] ...$groups
	 *
	 * @return string
	 */
	public function getHighest(string ...$groups) : string {
		$this->config->reload();
		$arr = array_map(function($group1, $group2) {
			$parents = $this->getRecursiveInheritance($group2);
			if(in_array($group1, $parents)) {
				return 1;
			}
			$parents = $this->getRecursiveInheritance($group1);
			if(in_array($group2, $parents)) {
				return -1;
			}
			return 0; // They are not in the same hierarchy don't do anything
		}, $groups);
		return $arr[0];
	}

	/**
	 * @param string $group
	 *
	 * @return array
	 */
	private function getRecursiveInheritance(string $group) : array {
		$groups = [];
		foreach($this->config->getNested("$group.inheritance", []) as $parentGroup) {
			$this->isAlias($parentGroup); //fixes alias to be real name
			foreach($this->getRecursiveInheritance($parentGroup) as $group) {
				$groups[] = $group;
			}
		}
		return array_unique($groups);
	}

	/**
	 * @param array $groups
	 *
	 * @return bool
	 */
	public function setDefaultGroups(array $groups) : bool {
		foreach ($groups as $group) {
			foreach ($this->defaultGroups as $old) {
				$this->getGroupsConfig()->setNested($old.".isDefault", false);
			}
			$this->defaultGroups = [];
			$this->defaultGroups[] = $group;
			$this->getGroupsConfig()->setNested($group.".isDefault", true);
		}
		return $this->getGroupsConfig()->save(true);
	}

	/**
	 * @param string $group
	 *
	 * @return bool
	 */
	public function addDefaultGroup(string $group) : bool {
		$this->defaultGroups[] = $group;
		$this->getGroupsConfig()->setNested($group.".isDefault", true);
		return $this->getGroupsConfig()->save(true);
	}

	/**
	 * @param string $group
	 *
	 * @return bool
	 */
	public function removeDefaultGroup(string $group) : bool {
		$key = array_search($group, $this->defaultGroups);
		unset($this->defaultGroups[$key]);
		$this->getGroupsConfig()->setNested($group.".isDefault", false);
		return $this->getGroupsConfig()->save(true);
	}

	/**
	 * @param string $group
	 * @param string $levelName
	 *
	 * @return string[]
	 */
	public function getGroupPermissions(string $group, string $levelName = "") : array {
		if(empty($levelName)) {
			return $this->config->getNested("$group.permissions", []);
		}else{
			return $this->config->getNested("$group.worlds.$levelName", []);
		}
	}

	/**
	 * @param string $group
	 * @param string $levelName
	 *
	 * @return string[]
	 */
	public function getAllGroupPermissions(string $group, string $levelName = "") : array {
		$groupPerms = array_merge($this->getGroupPermissions($group, $levelName), $this->getInheritedPermissions($group, $levelName));
		sort($groupPerms, SORT_NATURAL | SORT_FLAG_CASE);
		return array_unique($groupPerms);
	}

	/**
	 * @param string $group
	 * @param string[] $permissions
	 * @param string $levelName
	 *
	 * @return bool
	 */
	public function setGroupPermissions(string $group, array $permissions, string $levelName = "") : bool {
		if(empty($levelName)) {
			$this->config->setNested("$group.permissions", $permissions);
			return $this->config->save(true);
		}else{
			$this->config->setNested("$group.worlds.$levelName", $permissions);
			return $this->config->save(true);
		}
	}

	/**
	 * @param string $group
	 * @param string[] $permissions
	 * @param string $levelName
	 *
	 * @return bool
	 */
	public function addGroupPermissions(string $group, array $permissions = [], string $levelName = "") : bool {
		$permissions = array_merge($permissions, $this->getGroupPermissions($group, $levelName));
		return $this->setGroupPermissions($group, $permissions, $levelName);
	}

	/**
	 * @param string $group
	 * @param string[] $permissions
	 * @param string $levelName
	 *
	 * @return bool
	 */
	public function removeGroupPermissions(string $group, array $permissions = [], string $levelName = "") : bool {
		$perms = $this->getGroupPermissions($group, $levelName);
		foreach($permissions as $permission) {
			if(($key = array_search($permission, $this->getGroupPermissions($group, $levelName))) !== false) {
				unset($perms[$key]);
			}
		}
		return $this->setGroupPermissions($group, $perms, $levelName);
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
		foreach($this->plugin->getServer()->getLevels() as $level) {
			$permissions = array_unique($this->getGroupPermissions($group, $level->getName()));
			sort($permissions, SORT_NATURAL | SORT_FLAG_CASE);
			$this->setGroupPermissions($group, $permissions, $level->getName());
		}
		return true;
	}

	/**
	 * @param string $group
	 * @param string $levelName
	 *
	 * @return string[] includes set and unset permissions from config
	 */
	public function getInheritedPermissions(string $group, string $levelName = "") : array {
		$this->config->reload();
		$permissions = [];
		foreach($this->config->getNested("$group.inheritance", []) as $parentGroup) {
			$this->isAlias($parentGroup); //fixes alias to be real name
			foreach($this->getInheritedPermissions($parentGroup, $levelName) as $parentPermission) {
				$permissions[] = $parentPermission;
			}
		}
		foreach($this->getGroupPermissions($group, $levelName) as $permission) {
			$permissions[] = $permission;
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
	 * @param string $group
	 *
	 * @return bool
	 */
	public function isAlias(string &$group) : bool {
		if(in_array($group, $this->groupAliases)) {
			$group = array_search($group, $this->groupAliases);
			return true;
		}
		return false;
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
				$this->config->save(true);
				$this->sortGroupPermissions($group);
			}
		}else{
			$groups = $this->config->getAll();
			foreach($groups as $group => $data) {
				sort($data["permissions"], SORT_NATURAL | SORT_FLAG_CASE);
				$this->config->set($group, $data);
				$this->config->save(true);
				$this->sortGroupPermissions($group);
			}
		}
		return true;
	}
}