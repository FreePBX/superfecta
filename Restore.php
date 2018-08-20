<?php
namespace FreePBX\modules\Superfecta;
use FreePBX\modules\Backup as Base;
class Restore Extends Base\RestoreBase{
  public function runRestore($jobid){
    $this->FreePBX->Superfecta->loadConfigs($this->getConfigs());
  }

  public function processLegacy($pdo, $data, $tables, $unknownTables, $tmpfiledir){
    $tables = array_flip($tables+$unknownTables);
    if(!isset($tables['superfectaconfig'])){
      return $this;
    }
    $bmo = $this->FreePBX->Superfecta;
    $bmo->setDatabase($pdo);
    $configs = $bmo->dumpConfigs();
    $bmo->resetDatabase();
    $bmo->loadConfigs($configs);
    return $this;
  }

}
