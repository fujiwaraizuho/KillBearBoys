<?php

namespace KillBearBoys;

/* base */
use pocketmine\plugin\PluginBase;
use pocketmine\Player;
use pocketmine\Server;

/* command */
use pocketmine\command\Command;
use pocketmine\command\CommandSender;

/* event */
use pocketmine\event\Listener;
use pocketmine\event\Event;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;


class Main extends PluginBase implements Listener
{
	private $db;
	private $wands = [];

	public function onEnable()
	{
		Server::getInstance()->getPluginManager()->registerEvents($this, $this);

		if (!file_exists($this->getDataFolder())) {
			mkdir($this->getDataFolder(), 0744, true);
		}

		$this->db = \SQLite3($this->getDataFolder() . "data.db");

		$this->db->exec("CREATE TABLE IF NOT EXISTS block (
			id
			x,
			y,
			z,
			level,
			who,
			ip,
			cid,
			action,
			date,
			blockname,
			blockid,
			blockmeta
		)");

		$this->getLogger()->info("§aINFO§f > §aEnabled...");
	}


	public function onDisable()
	{
		$this->getLogger()->info("§cINFO§f > §cDisabled...");
	}


	public function onQuit(PlayerQuitEvent $event)
	{
		$name = $event->getPlayer()->getName();

		if (isset($this->wands[$name])) {
			unset($this->wands[$name]);
		}	
	}


	public function onTouch(PlayerInteractEvent $event)
	{
		$name = $event->getPlayer()->getName();
		$eventName = "Touch";

		if (!isset($this->wands[$name])) {
			$this->add($event, $eventName);
		} else {
			$this->show($event);
			$event->setCancelled(true);
		}
	}


	public function onBreak(BlockBreakEvent $event)
	{
		$name = $event->getPlayer()->getName();
		$eventName = "Break";

		if (!isset($this->wands[$name])) {
			$this->add($event, $eventName);
		} else {
			$this->show($event);
			$event->setCancelled(true);
		}
	}


	public function onPlace(BlockPlaceEvent $event)
	{
		$name = $event->getPlayer()->getName();
		$eventName = "Place";

		if (!isset($this->wands[$name])) {
			$this->add($event, $eventName);
		} else {
			$this->show($event);
			$event->setCancelled(true);
		}	
	}


	/**   API   **/
	public function add($event, string $action)
	{
		$player = $event->getPlayer();
		$name = $player->getName();
		$ip = $player->getAddress();
		$cid = $player->getClientId();

		$x = $event->getBlock()->x;
		$y = $event->getBlock()->y;
		$z = $event->getBlock()->z;

		$level = $event->getPlayer()->getLevel()->getName();

		$blockName = $event->getBlock()->getName();
		$blockId = $event->getBlock()->getId();
		$blockMeta = $event->getBlock()->getDamage();

		$time = date("Y/m/d - H:i:s", time());

		$value = "INSERT OR REPLACE INTO block VALUES (
			:id
			:x,
			:y,
			:z,
			:level,
			:who,
			:ip,
			:cid,
			:action,
			:date,
			:blocktime,
			:blockid,
			:blockmeta
		)";
		$db = $this->db->prepare($value);

		$db->bindValue(":id", "x". $x ."y". $y ."z". $z);
		$db->bindValue(":x", $x);
		$db->bindValue(":y", $y);
		$db->bindValue(":z", $z);
		$db->bindValue(":level", $level);
		$db->bindValue(":who", $name);
		$db->bindValue(":ip", $ip);
		$db->bindValue(":cid", $cid);
		$db->bindValue(":action", $action);
		$db->bindValue(":date", $time);
		$db->bindValue(":blockname", $blockName);
		$db->bindValue(":blockid", $blockId);
		$db->bindValue(":blockmeta", $blockMeta);

		$db->execute();
	}


	public function show($event)
	{
		$player = $evemt->getPlayer();

		$x = $event->getBlock()->x;
		$y = $event->getBlock()->y;
		$z = $event->getBlock()->z;

		$id = "x". $x ."y". $y ."z". $z;

		$value = "SELECT who, ip, cid, action, blockname, blockid, blockmeta, date FROM block WHERE id = :id";
		$db = $this->db->prepare($value);

		$db->bindValue(":id", $id);

		$data = $db->execute()->fetchArray(SQLITE3_ASSOC);

		var_dump($data);
	}


	public function onCommand(CommandSender $sender, Command $command, string $label, array $args):bool
	{
		$name = $sender->getName();

		if ($sender instanceof Player) {
			if (!isset($this->wands[$name])) {
				$this->wands[$name] = true;
				$sender->sendMessage("§a>> Enable！");
				return true;
			} else {
				unset($this->wands[$name]);
				$sender->sendMessage("§c>> Disable！");
				return true;
			}
		} else {
			$sender->sendMessage("§c>> Please run this command in game！");
			return true;
		}
	}
}