<?php
   namespace Hologram;
   
   use pocketmine\plugin\PluginBase;
   use pocketmine\Player;
   use pocketmine\Server;
   use pocketmine\entity\Entity;
   use pocketmine\math\Vector3;
   use pocketmine\event\Listener;
   use pocketmine\command\Command;
   use pocketmine\command\CommandSender;
   use pocketmine\event\player\PlayerJoinEvent;
   use pocketmine\event\entity\EntityDamageEvent;
   use pocketmine\event\entity\EntityDamageByEntityEvent;
   use pocketmine\nbt\tag\CompoundTag;
   use pocketmine\utils\Config;
   use pocketmine\event\player\PlayerMoveEvent;
   use pocketmine\nbt\tag\ByteTag;
   use pocketmine\nbt\tag\ListTag;
   use pocketmine\nbt\tag\DoubleTag;
   use pocketmine\nbt\tag\FloatTag;
   use pocketmine\nbt\tag\ShortTag;
   use pocketmine\nbt\tag\StringTag;
         
   class Hologram extends PluginBase implements Listener{
   	
   	public $config;
   	public $removers=array();
   private static $instance;
   	
   	public static function getInstance(){
   		return self::$instance;
   	}
   	
   	public function onEnable(){
   		self::$instance=$this;
   		$this->getServer()->getPluginManager()->registerEvents($this, $this);
   		Server::getInstance()->getLogger()->info("§dHologram §eStarting...");
   		Server::getInstance()->getCommandMap()->register("FloatingText", new Livecommands("hg"));
   		Entity::registerEntity(Text::class, true);
   		$this->loadConfig();
   	}
   	
   	public function loadConfig(){
   		$main=Hologram::getInstance();
   		 @mkdir($this->getDataFolder());
   		$this->config=new Config($this->getDataFolder() . "texts.yml", Config::YAML);
    		if(!$this->config->get("Hologram")){
   			$opt=[
   			"File"=>"welcome.txt"];
   			$this->config->set("Hologram", array());
   			$cfg=$this->config->get("Hologram");
   			$cfg["Welcome"]=$opt;
   			$this->config->set("Hologram", $cfg);
   	    touch($main->getDataFolder()."welcome.txt");
   	    $dosya=fopen($main->getDataFolder()."welcome.txt", "a");
   	    fwrite($dosya, "Welcome to the Hologram");
   	    fclose($dosya);
   			$this->config->save();
   		}
   	}
   	
   	public function onDamage(EntityDamageEvent $event){
   		$entity=$event->getEntity();
   		$main=Hologram::getInstance();
   		if($event instanceof EntityDamageByEntityEvent){
   			$damager=$event->getDamager();
   			if($damager instanceof Player){
   		 if(isset($main->removers[$damager->getName()])){
   		  	 $entity->close();
   		  	 $damager->sendMessage("§6[§5Hologram§6]§7 Hologram removed.");
   		  	 unset($main->removers[$damager->getName()]);
   		  }
   		 }
   		}
   	}
   	
   	public function replacedText(string $text){
   		$tps=$this->getServer()->getTicksPerSecond();
   		$onlines=count($this->getServer()->getOnlinePlayers());
   		$maxplayers=$this->getServer()->getMaxPlayers();
   		$worldsc=count($this->getServer()->getLevels());
   		$variables=[
   		"{line}"=>"\n",
   		"{tps}"=>$tps,
   		"{maxplayers}"=>$maxplayers,
   		"{onlines}"=>$onlines,
   		"{worldscount}"=>$worldsc];
   		foreach($variables as $var=>$ms){
   			$text=str_ireplace($var, $ms, $text);
   		}
   		return $text;
   	}
   	
   	public function createHologram($x, $y, $z, $skin, $skinId, $inv, $yaw, $pitch, $chunk, $tag, $name, $file=""){
   	 $nbt = new CompoundTag;
   	 $nbt->Pos = new ListTag("Pos", [
   	 new DoubleTag("", $x),
   	 new DoubleTag("", $y),
   	 new DoubleTag("", $z)
   	 ]);
    $nbt->Rotation = new ListTag("Rotation", [
    new FloatTag("", $yaw),
    new FloatTag("", $pitch)
    ]);
    $nbt->Inventory = new ListTag("Inventory", $inv);
    $nbt->Skin = new CompoundTag("Skin", ["Data" => new StringTag("Data", $skin), "Name" => new StringTag("Name", $skinId)]);
    $nbt->Health = new ShortTag("Health", 20);
    $nbt->Invulnerable = new ByteTag("Invulnerable", 1);
    $nbt->HologramName= new StringTag("Hologramname", $name);
    $nbt->CustomName=new StringTag("CustomName", $tag);
    $nbt->infos=new ListTag("infos", ["file"=>$file, "datafolder"=>$this->getDataFolder()."$name"]);
   		$entity=Entity::createEntity("Text", $chunk, $nbt, $tag);
   		$entity->spawnToAll();
   	}
   }
   
   class Livecommands extends Command{
   	
   	private $name;
   	
   	public function __construct($name){
   		parent::__construct(
   		$name,
   		"Hologram plugin main Command",
   		"/hg <add|cancel|remove>");
   		$this->setPermission("Hologram.command.use");
   	}
   	
   	public function execute(CommandSender $s, $label, array $args){
    if(!$s->hasPermission("Hologram.command.use")){
      return true;
    }
    $help="§5Hologream §6Help Page\n
    §4- §7/hg addtext <text(unlimited args)> :§5 Add Hologram. You can use {line} for new line\n
    §4- §7/hg add <TextName> :§5 Add Hologram with file\n
    §4- §7/hg remove :§5 Remove a Hologram when Tap a entity";
   		if(!empty($args[0])){
   			$main=Hologram::getInstance();
   			$core=Hologram::getInstance();
   			switch($args[0]){
   				case "addtext":
   				    array_shift($args);
   				    $text="";
   				    foreach($args as $t){
   				    	$text.=$t." ";
   				    }
   				    $replaced=$main->replacedText($text);
   				    $main->createHologram($s->x, $s->y - 1, $s->z, $s->getSkinData(), $s->getSkinId(), $s->getInventory(), $s->yaw, $s->pitch, $s->chunk, $replaced, $args[0]);
   				    $s->sendMessage("§6[§5Hologram§6]§7 §eHologram created(not file)");
   				    break;
   				case "add":
   				    if(!empty($args[1])){
   				    	  $file=$args[1];
   				    	  if($main->config->getNested("Hologram.$file")){
   				    	  	  $ad=$main->config->getNested("Hologram.$file")["File"];
   				    	  	  $dosya=fopen($core->getDataFolder()."$ad", "r");
   				    	  	  $yazi=fread($dosya, filesize($core->getDataFolder()."$ad"));
   				    	  	  fclose($dosya);
   				    	  	  $x=$s->x;
   				    	  	  $y=$s->y - 1;
   				    	  	  $z=$s->z;
   				    	  	  $skin=$s->getSkinData();
   				    	  	  $skinId=$s->getSkinId();
   				    	  	  $yaw=$s->yaw;
   				    	  	  $pitch=$s->pitch;
   				    	  	  $inv=$s->getInventory();
   				    	  	  $main->createHologram($x, $y, $z, $skin, $skinId, $inv, $yaw, $pitch, $s->chunk, $yazi, $args[1], $dosya);
   				    	  	  $s->sendMessage("§6[§5Hologram§6]§7 Text created.");
   				    	  }else{
   				    	  	 $s->sendMessage("§6[§5Hologram§6]§7 §cText not found on texts.yml");
   				    	  }
   				    }else{
   				    	 $s->sendMessage("§eUsage: /hg add <textname>");
   				    }
   				    break;
   				case "updateall":
   				    $levels=$main->getServer()->getLevels();
   				    foreach($levels as $level){
   				    	$entities=$level->getEntities();
   				    	foreach($entities as $entity){
   				    		if(isset($entity->namedtag->HologramName)){
   				    			if(!isset($entity->namedtag->infos)){
   				    				$ad=$entity->namedtag->HologramName;
   				    	  	$dosya=fopen($core->getDataFolder()."$ad", "r");
   				    	  	$yazi=fread($dosya, filesize($core->getDataFolder()."$ad"));
   				    	  	fclose($dosya);
   				    				$main->createHologram($entity->x, $entity->y + 1, $entity->z, $entity->getSkinData(), $entity->getSkinId(), $entity->getInventory(), $entity->yaw, $entity->pitch, $entity->chunk, $entity->namedtag->CustomName, "$ad", $dosya);
   				    				$entity->close();
   				    			}
   				    		}
   				    	}
   				    }
   				    $s->sendMessage("§6[§5Hologram§6]§7 All old Hologram has been updated!");
   				    break;
               case "tpme":
       if(!empty($args[1])){
       	$entities=$s->getLevel()->getEntities();
       	$target=null;
       	foreach($entities as $text){
       		if($text instanceof Text){
       			if(strpos($args[1], $text->getNameTag())!=false or $text->getName()==$args[1]){
       				$target=$text;
       			}
       		}
       	}
       	if($target!=null){
       		$target->teleport($s);
       		$s->sendMessage("§6[§5Hologram§6]§7 §aHologram teleported to you");
       	}else{
       		$s->sendMessage("§6[§5Hologram§6]§7 §cInvalid Hologram!");
       	}
       }else{
       	$s->sendMessage("§6[§5Hologram§6]§7 §eUsage: /lt tpme <Hologramname>");
       }
       break;
   		  case "cancel":
   		      if(isset($main->removers[$s->getName()])){
   		      	 unset($main->removers[$s->getName()]);
   		      }
   		      $s->sendMessage("§6[§5Hologram§6]§7 Event cancelled.");
   		      break;
   		  case "remove":
   		      $main->removers[$s->getName()]=true;
   		      $s->sendMessage("§6[§5Hologram§6]§7 Please Touch a Hologram now.");
   		      break;
     	}
   }else{
   	 $s->sendMessage($help);
   }
  }
 }
?>
