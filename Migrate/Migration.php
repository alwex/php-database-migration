<?php
/**
 * Created by PhpStorm.
 * User: aguidet
 * Date: 28/02/15
 * Time: 18:54
 */

namespace Migrate;

use Cocur\Slugify\Slugify;
use Migrate\Utils\ArrayUtil;

class Migration
{
    private $id;
    private $description;
    private $file;
    private $appliedAt;
    private $version;
    private $sqlUp;
    private $sqlDown;

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return mixed
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param mixed $description
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }

    /**
     * @return mixed
     */
    public function getFile()
    {
        return $this->file;
    }

    /**
     * @param mixed $file
     */
    public function setFile($file)
    {
        $this->file = $file;
    }

    /**
     * @return mixed
     */
    public function getAppliedAt()
    {
        return $this->appliedAt;
    }

    /**
     * @param mixed $appliedAt
     */
    public function setAppliedAt($appliedAt)
    {
        $this->appliedAt = $appliedAt;
    }

    /**
     * @return mixed
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * @param mixed $version
     */
    public function setVersion($version)
    {
        $this->version = $version;
    }

    /**
     * @return mixed
     */
    public function getSqlUp()
    {
        return $this->sqlUp;
    }

    /**
     * @param mixed $sqlUp
     */
    public function setSqlUp($sqlUp)
    {
        $this->sqlUp = $sqlUp;
    }

    /**
     * @return mixed
     */
    public function getSqlDown()
    {
        return $this->sqlDown;
    }

    /**
     * @param mixed $sqlDown
     */
    public function setSqlDown($sqlDown)
    {
        $this->sqlDown = $sqlDown;
    }

    public static function createFromFile($filename, $migrationDir)
    {
        $data = explode('_', $filename);

        $migration = new self();
        $migration->setId($data[0]);
        $migration->setAppliedAt(null);
        $migration->setVersion(null);
        $migration->setDescription(str_replace('.sql', '', str_replace('-', ' ', $data[1])));
        $migration->setFile($filename);
        $migration->load($migrationDir);

        return $migration;
    }

    public static function createFromRow(array $data, $migrationDir)
    {
        $migration = new self();
        $migration->setId(ArrayUtil::get($data, 'id'));
        $migration->setAppliedAt(ArrayUtil::get($data, 'applied_at'));
        $migration->setVersion(ArrayUtil::get($data, 'version'));
        $migration->setDescription(ArrayUtil::get($data, 'description'));

        $slugger = new Slugify();
        $filename = $migration->getId() . '_' . $slugger->slugify($migration->getDescription()) . '.sql';
        $migration->setFile($filename);

        $migration->load($migrationDir);

        return $migration;
    }

    public function toArray()
    {
        return array(
            $this->getId(),
            $this->getVersion(),
            $this->getAppliedAt(),
            $this->getDescription()
        );
    }

    public function load($migrationDir)
    {
        // construct migration file's path
        $file = $migrationDir . '/' . $this->getFile();

        // does the migration file exists?
        if (file_exists($file)) {
            // get the contents of the file ...
            $content = file_get_contents($migrationDir . '/' . $this->getFile());
            // ... and split it into the UP and DOWN migration sections
            if ($content && strpos($content, '@UNDO') > 0) {
                $sql = explode('-- @UNDO', $content);
                $this->setSqlUp($sql[0]);
                $this->setSqlDown($sql[1]);
            }
        // the migration is missing
        } else {
            // output the file name so the user can do some cleanup
            echo "Missing migration file: $file\n";
        }
    }
}
