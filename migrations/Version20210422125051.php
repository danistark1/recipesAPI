<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210422125051 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE recipesEntity (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, prep_time VARCHAR(100) DEFAULT NULL, cooking_time VARCHAR(100) DEFAULT NULL, servings VARCHAR(255) DEFAULT NULL, category VARCHAR(100) NOT NULL, directions LONGTEXT NOT NULL, insert_date_time VARCHAR(255) NOT NULL, favourites INT NOT NULL, added_by VARCHAR(100) DEFAULT NULL, calories VARCHAR(100) DEFAULT NULL, cuisine VARCHAR(255) DEFAULT NULL, ingredients LONGTEXT NOT NULL, url VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE recipesLogger (id INT AUTO_INCREMENT NOT NULL, message VARCHAR(255) DEFAULT NULL, context LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:array)\', level INT DEFAULT NULL, level_name VARCHAR(255) DEFAULT NULL, extra LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:array)\', insert_date_time DATETIME NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE recipesEntity');
        $this->addSql('DROP TABLE recipesLogger');
    }
}
