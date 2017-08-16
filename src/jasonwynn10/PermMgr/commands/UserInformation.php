<?php
namespace jasonwynn10\PermMgr\commands;

use jasonwynn10\PermMgr\ThePermissionManager;

use pocketmine\command\CommandSender;
use pocketmine\command\PluginCommand;
use pocketmine\Player;
use pocketmine\plugin\Plugin;
use pocketmine\utils\TextFormat;

class UserInformation extends PluginCommand {
	/**
	 * UserInformation constructor.
	 *
	 * @param ThePermissionManager $plugin
	 */
	public function __construct(ThePermissionManager $plugin) {
		parent::__construct($plugin->getLanguage()->get("userinformation.name"), $plugin);
		$this->setPermission("PermManager.command.userinformation");
		$this->setUsage($plugin->getLanguage()->get("userinformation.usage"));
		$this->setAliases([$plugin->getLanguage()->get("userinformation.alias")]);
		$this->setDescription($plugin->getLanguage()->get("userinformation.desc"));
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
			$player->sendMessage(TextFormat::YELLOW.$this->getPlugin()->getLanguage()->translateString("userinformation.header", [$player->getName()]));
			$status = $this->getPlugin()->getLanguage()->translateString("userinformation.online");
			$player->sendMessage(TextFormat::YELLOW.$this->getPlugin()->getLanguage()->translateString("userinformation.status", [$status]));
			$player->sendMessage(TextFormat::YELLOW.$this->getPlugin()->getLanguage()->translateString("userinformation.ip", [$player->getAddress()]));
			$player->sendMessage(TextFormat::YELLOW.$this->getPlugin()->getLanguage()->translateString("userinformation.uuid", [$player->getUniqueId()]));
			$player->sendMessage(TextFormat::YELLOW.$this->getPlugin()->getLanguage()->translateString("userinformation.group", [$this->getPlugin()->getPlayerProvider()->getGroup($player)]));
		} else {
			$player = $this->getPlugin()->getServer()->getOfflinePlayer($args[0]);
			$status = $this->getPlugin()->getLanguage()->translateString("userinformation.offline");
			$player->sendMessage(TextFormat::YELLOW.$this->getPlugin()->getLanguage()->translateString("userinformation.status", [$status]));
			$player->sendMessage(TextFormat::YELLOW.$this->getPlugin()->getLanguage()->translateString("userinformation.ip", [$player->getAddress()]));
			$player->sendMessage(TextFormat::YELLOW.$this->getPlugin()->getLanguage()->translateString("userinformation.uuid", [$player->getUniqueId()]));
			$player->sendMessage(TextFormat::YELLOW.$this->getPlugin()->getLanguage()->translateString("userinformation.group", [$this->getPlugin()->getPlayerProvider()->getGroup($player)]));
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
		$worlds = [];
		foreach($this->getPlugin()->getServer()->getLevels() as $level) {
			$worlds[] = $level->getName();
		}
		sort($worlds, SORT_FLAG_CASE);
		$commandData["overloads"]["default"]["input"]["parameters"] = [
			[
				"name" => "player",
				"type" => "stringenum",
				"optional" => false,
				"enum_values" => $players
			],
			[
				"name" => "world",
				"type" => "stringenum",
				"optional" => true,
				"enum_values" => $worlds
			]
		];
		$commandData["permission"] = $this->getPermission();
		return $commandData;
	}
}