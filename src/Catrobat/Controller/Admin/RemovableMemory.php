<?php

namespace App\Catrobat\Controller\Admin;

class RemovableMemory
{
  public string $name;

  public string $description;

  public $size;

  public $size_raw;

  public $command_link;

  public $command_name;

  public $download_link;

  public $execute_link;

  public $archive_command_link;

  public $archive_command_name;

  public function __construct(string $name = '', string $description = '')
  {
    $this->name = $name;
    $this->description = $description;
  }

  /**
   * @param mixed $size
   */
  public function setSize($size): void
  {
    $this->size = $size;
  }

  /**
   * @param mixed $size
   */
  public function setSizeRaw($size): void
  {
    $this->size_raw = $size;
  }

  /**
   * @param mixed $command
   */
  public function setCommandLink($command): void
  {
    $this->command_link = $command;
  }

  /**
   * @param mixed $command
   */
  public function setCommandName($command): void
  {
    $this->command_name = $command;
  }

  /**
   * @param mixed $download_link
   */
  public function setDownloadLink($download_link): void
  {
    $this->download_link = $download_link;
  }

  /**
   * @return mixed
   */
  public function getArchiveCommandLink()
  {
    return $this->archive_command_link;
  }

  /**
   * @param mixed $archive_command_link
   */
  public function setArchiveCommandLink($archive_command_link): void
  {
    $this->archive_command_link = $archive_command_link;
  }

  /**
   * @return mixed
   */
  public function getArchiveCommandName()
  {
    return $this->archive_command_name;
  }

  /**
   * @param mixed $archive_command_name
   */
  public function setArchiveCommandName($archive_command_name): void
  {
    $this->archive_command_name = $archive_command_name;
  }

  /**
   * @param mixed $execute_link
   */
  public function setExecuteLink($execute_link): void
  {
    $this->execute_link = $execute_link;
  }
}
