<?php
$installer = $this;

$installer->installEntities();

Mage::getSingleton('adminhtml/session')->addSuccess("Visual Stock Levels Installer Successful: 0.1.0.");
