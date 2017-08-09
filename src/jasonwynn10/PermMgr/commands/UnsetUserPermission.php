<?php
namespace jasonwynn10\PermMgr\commands;

use jasonwynn10\PermMgr\ThePermissionManager;

use pocketmine\command\CommandSender;
use pocketmine\command\PluginCommand;
use pocketmine\permission\Permission;
use pocketmine\Player;
use pocketmine\plugin\Plugin;
use pocketmine\utils\TextFormat;

class UnsetUserPermission extends PluginCommand {
	/**
	 * UnsetUserPermission constructor.
	 *
	 * @param ThePermissionManager $plugin
	 */
	public function __construct(ThePermissionManager $plugin){
		parent::__construct($plugin->getLanguage()->get("unsetuserpermission.name"), $plugin);
		$this->setPermission("PermManager.command.unsetuserpermission");
		$this->setUsage($plugin->getLanguage()->get("unsetuserpermission.usage"));
		$this->setAliases([$plugin->getLanguage()->get("unsetuserpermission.alias")]);
		$this->setDescription($plugin->getLanguage()->get("unsetuserpermission.desc"));
	}

	/**
	 * @param CommandSender $sender
	 * @param string $commandLabel
	 * @param string[] $args
	 *
	 * @return bool
	 */
	public function execute(CommandSender $sender, string $commandLabel, array $args){
		parent::execute($sender, $commandLabel, $args);
		if(empty($args)) {
			return false;
		}
		$player = $this->getPlugin()->getServer()->getPlayer($args[0]);
		if($player instanceof Player) {
			$perm = new Permission($args[1]);
			$this->getPlugin()->addPlayerPermission($player, $perm);
			$sender->sendMessage(TextFormat::GREEN.$this->getPlugin()->getLanguage()->translateString("unsetuserpermission.success", [$args[0]]));
		} else {
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
		$commandData["overloads"]["default"]["input"]["parameters"] = [
			[
				"name" => "player",
				"type" => "target",
				"optional" => false
			],
			[
				"name" => "permission",
				"type" => "rawtext",
				"optional" => false
			]
		];
		return $commandData;
	}
}