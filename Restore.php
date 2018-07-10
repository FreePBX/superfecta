<?php
namespace FreePBX\modules\Superfecta;
use FreePBX\modules\Backup as Base;
class Restore Extends Base\RestoreBase{
  public function runRestore($jobid){
    $this->FreePBX->Superfecta->loadConfigs($this->getConfigs());
  }
}