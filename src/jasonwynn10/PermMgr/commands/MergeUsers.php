<?php
namespace jasonwynn10\PermMgr\commands;

use jasonwynn10\PermMgr\ThePermissionManager;

use pocketmine\command\CommandSender;
use pocketmine\command\PluginCommand;
use pocketmine\Player;
use pocketmine\plugin\Plugin;
use pocketmine\utils\TextFormat;

class MergeUsers extends PluginCommand {
	/**
	 * ReloadPermissions constructor.
	 *
	 * @param ThePermissionManager $plugin
	 */
	public function __construct(ThePermissionManager $plugin) {
		parent::__construct($plugin->getLanguage()->get("mergeusers.name"), $plugin);
		$this->setPermission("PermManager.command.mergeusers");
		$this->setUsage($plugin->getLanguage()->get("mergeusers.usage"));
		$this->setAliases([$plugin->getLanguage()->get("mergeusers.alias")]);
		$this->setDescription($plugin->getLanguage()->get("mergeusers.desc"));
		$this->setPermissionMessage($plugin->getLanguage()->get("nopermission"));
	}

	/**
	 * @param CommandSender $sender
	 * @param string $commandLabel
	 * @param string[] $args
	 *
	 * @return bool
	 */
	public function execute(CommandSender $sender, string $commandLabel, array $args) : bool {
		if(!$this->testPermission($sender)) {
			return true;
		}
		if(empty($args) or count($args) < 2) {
			return false;
		}
		var_dump($args); //TODO remove
		$from = $this->getPlugin()->getServer()->getOfflinePlayer($args[0])->getPlayer() ?? $this->getPlugin()->getServer()->getOfflinePlayer($args[0]);
		$to = $this->getPlugin()->getServer()->getOfflinePlayer($args[1])->getPlayer() ?? $this->getPlugin()->getServer()->getOfflinePlayer($args[1]);
		$fromGroup = $this->getPlugin()->getPlayerProvider()->getGroup($from);
		$toGroup = $this->getPlugin()->getPlayerProvider()->getGroup($to);
		$group = $this->getPlugin()->getGroups()->getHighest($fromGroup, $toGroup);
		if(!$this->getPlugin()->getPlayerProvider()->mergePermissions($from, $to) or !$this->getPlugin()->getPlayerProvider()->setGroup($to, $group)) {
			$sender->sendMessage(TextFormat::YELLOW.$this->getPlugin()->getLanguage()->translateString("error"));
		}else{
			$sender->sendMessage(TextFormat::YELLOW.$this->getPlugin()->getLanguage()->translateString("mergeuser.success", $to->getName()));
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
		$players = [$player->getName()];
		foreach($this->getPlugin()->getServer()->getOnlinePlayers() as $player) {
			$players[] = $player->getName();
		}
		sort($players, SORT_NATURAL | SORT_FLAG_CASE);
		$commandData["overloads"]["default"]["input"]["parameters"] = [
			[
				"name" => "from",
				"type" => "stringenum",
				"optional" => false,
				"enum_values" => $players
			],
			[
				"name" => "to",
				"type" => "stringenum",
				"optional" => false,
				"enum_values" => $players
			]
		];
		$commandData["permission"] = $this->getPermission();
		return $commandData;
	}
}