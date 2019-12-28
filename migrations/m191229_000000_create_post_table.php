<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%post}}`.
 */
class m191229_000000_create_post_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%post}}', [
            'id' => $this->primaryKey(),
            'title' => $this->string()->notNull(),
            'body' => $this->text()->notNull(),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ]);

        $this->createIndex('idx-title', '{{%post}}', 'title');
        $this->createIndex('idx-created_at', '{{%post}}', 'created_at');
        $this->createIndex('idx-updated_at', '{{%post}}', 'updated_at');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropIndex('idx-title', '{{%post}}');
        $this->dropIndex('idx-created_at', '{{%post}}');
        $this->dropIndex('idx-updated_at', '{{%post}}');

        $this->dropTable('{{%post}}');
    }
}
