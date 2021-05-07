<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210507205924 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE categories_entity (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(50) NOT NULL, type VARCHAR(50) NOT NULL, insert_date_time DATETIME NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE recipesConfiguration (id INT AUTO_INCREMENT NOT NULL, config_key VARCHAR(255) NOT NULL, config_value VARCHAR(255) DEFAULT NULL, insert_date_time DATETIME NOT NULL, config_type VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE recipesEntity (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, prepTime VARCHAR(100) DEFAULT NULL, cookingTime VARCHAR(100) DEFAULT NULL, servings VARCHAR(255) DEFAULT NULL, category VARCHAR(100) NOT NULL, directions LONGTEXT NOT NULL, insertDateTime VARCHAR(255) NOT NULL, favourites TINYINT(1) NOT NULL, addedBy VARCHAR(100) DEFAULT NULL, calories VARCHAR(100) DEFAULT NULL, cuisine VARCHAR(255) DEFAULT NULL, ingredients LONGTEXT NOT NULL, url VARCHAR(255) DEFAULT NULL, featured TINYINT(1) NOT NULL, INDEX IDX_8C870C975E237E06 (name), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE recipesLogger (id INT AUTO_INCREMENT NOT NULL, message VARCHAR(255) DEFAULT NULL, context LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:array)\', level INT DEFAULT NULL, level_name VARCHAR(255) DEFAULT NULL, extra LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:array)\', insert_date_time DATETIME NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE categories_entity');
        $this->addSql('DROP TABLE recipesConfiguration');
        $this->addSql('DROP TABLE recipesEntity');
        $this->addSql('DROP TABLE recipesLogger');
    }
}
