<?php
namespace Drush\Commands\core;

use Consolidation\AnnotatedCommand\AnnotatedCommand;
use Consolidation\AnnotatedCommand\CommandData;
use Drush\Command\DrushInputAdapter;
use Drush\Commands\DrushCommands;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class TopicCommands extends DrushCommands {

  /**
   * Read detailed documentation on a given topic.
   *
   * @command core-topic
   * @param $topic_name  The name of the topic you wish to view. If omitted, list all topic descriptions (and names in parenthesis).
   * @usage drush topic
   *   Pick from all available topics.
   * @usage drush topic docs-repl
   *   Show documentation for the Drush interactive shell
   * @usage drush docs-r
   *   Filter topics for those starting with 'docs-r'.
   * @remote-tty
   * @aliases topic
   * @topics docs-readme
   * @complete \Drush\Commands\core\TopicCommands::complete
   */
  public function topic($topic_name) {
    $application = \Drush::getApplication();
    $input = new DrushInputAdapter([$topic_name], []);
    return $application->run($input);
  }

  /**
   * @hook interact topic
   */
  public function interact(InputInterface $input, OutputInterface $output) {
    $topics = self::getAllTopics();
    $topic_name = $input->getArgument('topic_name');
    if (!empty($topic_name)) {
      // Filter the topics to those matching the query.
      foreach ($topics as $key => $topic) {
        if (strstr($key, $topic_name) === FALSE) {
          unset($topics[$key]);
        }
      }
    }
    if (count($topics) > 1) {
      // Show choice list.
      foreach ($topics as $key => $topic) {
        $choices[$key] = $topic->getDescription();
      }
      natcasesort($choices);
      if (!$topic_name = drush_choice($choices, dt('Choose a topic'), '!value (!key)', array(5))) {
        return drush_user_abort();
      }
      $input->setArgument('topic_name', $topic_name);
    }
  }

  /**
   * @hook validate topic
   */
  public function validate(CommandData $commandData) {
    $topic_name = $topic_name = $commandData->input()->getArgument('topic_name');
    if (empty($topic_name)) {
      throw new \Exception(dt("!topic topic not found.", array('!topic' => $topic_name)));
    }
  }

  /**
   * Retrieve all defined topics
   *
   * @return Command[]
   */
  static function getAllTopics() {
    /** @var Application $application */
    $application = \Drush::getApplication();
    $all = $application->all();
    foreach ($all as $key => $command) {
      if ($command instanceof AnnotatedCommand) {
        /** @var \Consolidation\AnnotatedCommand\AnnotationData $annotationData */
        $annotationData = $command->getAnnotationData();
        if ($annotationData->has('topic')) {
          $topics[$key] = $command;
        }
      }
    }
    return $topics;
  }

  public function complete() {
    return array('values' => array_keys(self::getAllTopics()));
  }
}
