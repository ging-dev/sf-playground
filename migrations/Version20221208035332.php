<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20221208035332 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__user AS SELECT id, google_id, roles, name, pictrue FROM user');
        $this->addSql('DROP TABLE user');
        $this->addSql('CREATE TABLE user (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, google_id VARCHAR(180) NOT NULL, roles CLOB NOT NULL --(DC2Type:json)
        , name VARCHAR(255) NOT NULL, picture VARCHAR(255) NOT NULL)');
        $this->addSql('INSERT INTO user (id, google_id, roles, name, picture) SELECT id, google_id, roles, name, pictrue FROM __temp__user');
        $this->addSql('DROP TABLE __temp__user');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D64976F5C865 ON user (google_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__user AS SELECT id, google_id, roles, name, picture FROM user');
        $this->addSql('DROP TABLE user');
        $this->addSql('CREATE TABLE user (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, google_id VARCHAR(180) NOT NULL, roles CLOB NOT NULL --(DC2Type:json)
        , name VARCHAR(255) NOT NULL, pictrue VARCHAR(255) NOT NULL)');
        $this->addSql('INSERT INTO user (id, google_id, roles, name, pictrue) SELECT id, google_id, roles, name, picture FROM __temp__user');
        $this->addSql('DROP TABLE __temp__user');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D64976F5C865 ON user (google_id)');
    }
}
