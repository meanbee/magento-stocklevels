<?php

try {
    $installer = $this;

    $installer->installEntities();

    Mage::getSingleton('adminhtml/session')->addSuccess("Visual Stock Levels Installer Successful: 0.1.0.");
} catch (Exception $e) {
    Mage::getSingleton('adminhtml/session')->addError("An error has occurred installing Visual Stock Levels: ". $e->getMessage());
} 

