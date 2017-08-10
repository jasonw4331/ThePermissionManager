<?php
namespace jasonwynn10\PermMgr\commands;

use jasonwynn10\PermMgr\ThePermissionManager;

use pocketmine\command\CommandSender;
use pocketmine\command\PluginCommand;
use pocketmine\Player;
use pocketmine\plugin\Plugin;
use pocketmine\utils\TextFormat;

class SetGroup extends PluginCommand {
	/**
	 * SetGroup constructor.
	 *
	 * @param ThePermissionManager $plugin
	 */
	public function __construct(ThePermissionManager $plugin) {
		parent::__construct($plugin->getLanguage()->get("setgroup.name"), $plugin);
		$this->setPermission("PermManager.command.setgroup");
		$this->setUsage($plugin->getLanguage()->get("setgroup.usage"));
		$this->setAliases([$plugin->getLanguage()->get("setgroup.alias")]);
		$this->setDescription($plugin->getLanguage()->get("setgroup.desc"));
		$this->setPermissionMessage($plugin->getLanguage()->get("nopermission"));
	}

	/**
	 * @param CommandSender $sender
	 * @param string $commandLabel
	 * @param string[] $args
	 *
	 * @return bool
	 */
	public function execute(CommandSender $sender, string $commandLabel, array $args) {
		if(!$this->testPermission($sender)) {
			return true;
		}
		if(empty($args)) {
			return false;
		}
		$player = $this->getPlugin()->getServer()->getPlayer($args[0]);
		if($player instanceof Player) {
			if(isset($args[1])) {
				$group = $args[1];
				if(!in_array($group, array_keys($this->getPlugin()->getGroups()->getAll())) and !in_array($group, $this->getPlugin()->getGroupAliases())) {
					$sender->sendMessage(TextFormat::DARK_RED.$this->getPlugin()->getLanguage()->translateString("invalidgroup", [$group]));
					return true;
				}
				if(!$this->getPlugin()->setPlayerGroup($player, $group)) {
					$sender->sendMessage(TextFormat::DARK_RED.$this->getPlugin()->getLanguage()->translateString("error"));
					return true;
				}else{
					$sender->sendMessage(TextFormat::GREEN.$this->getPlugin()->getLanguage()->translateString("setgroup.success", [$player->getName(), $group]));
					return true;
				}
			}else{
				return false;
			}
		}else{
			$sender->sendMessage(TextFormat::DARK_RED.$this->getPlugin()->getLanguage()->translateString("playeroffline", [$args[0]]));
		}
		return true;
	}

	/**
	 * @return ThePermissionManager
	 */
	public function getPlugin() : Plugin {
		return parent::getPlugin();
	}

	/**
	 * @param Player $player
	 *
	 * @return array
	 */
	public function generateCustomCommandData(Player $player) : array {
		$commandData = parent::generateCustomCommandData($player);
		$players = [];
		foreach($this->getPlugin()->getServer()->getOnlinePlayers() as $player) {
			$players[] = $player->getName();
		}
		sort($players, SORT_FLAG_CASE);
		$groups = [];
		foreach($this->getPlugin()->getGroups()->getAll() as $group => $data) {
			$groups[] = $group;
		}
		sort($groups, SORT_FLAG_CASE);
		$commandData["overloads"]["default"]["input"]["parameters"] = [
			[
				"name" => "player",
				"type" => "stringenum",
				"optional" => false,
				"enum_values" => $players
			],
			[
				"name" => "group",
				"type" => "stringenum",
				"optional" => false,
				"enum_values" => $groups
			]
		];
		$commandData["permission"] = $this->getPermission();
		return $commandData;
	}
}