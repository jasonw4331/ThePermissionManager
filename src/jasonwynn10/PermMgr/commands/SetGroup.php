<?php
namespace jasonwynn10\PermMgr\commands;

use jasonwynn10\PermMgr\ThePermissionManager;

use pocketmine\command\CommandSender;
use pocketmine\command\ConsoleCommandSender;
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
	public function execute(CommandSender $sender, string $commandLabel, array $args) : bool {
		if(!$this->testPermission($sender)) {
			return true;
		}
		if(empty($args)) {
			return false;
		}
		$player = $this->getPlugin()->getServer()->getOfflinePlayer($args[0])->getPlayer() ?? $this->getPlugin()->getServer()->getOfflinePlayer($args[0]);
		if(isset($args[1])) {
			$group = $args[1];
			if(!in_array($group, $this->getPlugin()->getGroups()->getGroupsConfig()->getAll(true)) and !$this->getPlugin()->isAlias($group)) {
				$sender->sendMessage(TextFormat::DARK_RED.$this->getPlugin()->getLanguage()->translateString("invalidgroup", [$group]));
				return true;
			}
			if(in_array($group, $this->getPlugin()->getSuperAdmins())) {
				if($sender instanceof ConsoleCommandSender) {
					if(!$this->getPlugin()->setPlayerGroup($player, $group)) {
						$sender->sendMessage(TextFormat::DARK_RED.$this->getPlugin()->getLanguage()->translateString("error"));
						return true;
					}else{
						$sender->sendMessage(TextFormat::GREEN.$this->getPlugin()->getLanguage()->translateString("setgroup.success", [$player->getName(), $group]));
						if($player->isOnline())
							$player->sendMessage(TextFormat::YELLOW.$this->getPlugin()->getLanguage()->translateString("setgroup.notify", [$group]));
						return true;
					}
				}else{
					$sender->sendMessage(TextFormat::DARK_RED.$this->getPlugin()->getLanguage()->translateString("error"));
					return true;
				}
			}
			if(!$this->getPlugin()->setPlayerGroup($player, $group)) {
				$sender->sendMessage(TextFormat::DARK_RED.$this->getPlugin()->getLanguage()->translateString("error"));
				return true;
			}else{
				$sender->sendMessage(TextFormat::GREEN.$this->getPlugin()->getLanguage()->translateString("setgroup.success", [$player->getName(), $group]));
				if($player->isOnline())
					$player->sendMessage(TextFormat::YELLOW.$this->getPlugin()->getLanguage()->translateString("setgroup.notify", [$group]));
				return true;
			}
		}else{
			return false;
		}
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
		sort($players, SORT_FLAG_CASE);
		$groups = $this->getPlugin()->getGroups()->getGroupsConfig()->getAll(true);
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