<?php

require_once('input_values.php');

class qtype_stack_api_export
{

    private $defaults;
    private $question;

    function qtype_stack_api_export(string $questionxml, $defaults)
    {
        $qob = new SimpleXMLElement($questionxml);
        $question = ($qob->question)? $qob->question: $qob;
        $this->defaults = $defaults;
        $this->question = $question;
    }

    private function property(&$yaml, $propertyName, $value, $type, $section) {
        $value = self::processValue($value, $type);
        $value = qtype_stack_api_input_values::get_yaml_value($propertyName, $value);
        if (!$this->defaults->isDefault($section, $propertyName, $value)) {
            $yaml[$propertyName] = $value;
        }
    }

    private static function processValue($value, string $type) {
        switch($type) {
            case "string":
                return (string) $value;
            case "int":
                return (int) $value;
            case "float":
                return (float) $value;
            case "bool":
                return (bool) $value;
        }
    }

    public function YAML()
    {

        $yaml = array();
        // general properties
        $q = $this->question;
        $section = 'main';
        self::property($yaml, 'name', $q->name->text, 'string', $section);
        self::property($yaml, 'default_mark', $q->defaultgrade, 'float', $section);
        self::property($yaml, 'question_html', $q->questiontext->text, 'string', $section);
        self::property($yaml, 'penalty', $q->penalty, 'float', $section);
        self::property($yaml, 'variables', $q->questionvariables->text, 'string', $section);
        self::property($yaml, 'note', $q->questionnote->text, 'string', $section);
        self::property($yaml, 'worked_solution_html', $q->generalfeedback->text, 'string', $section);
        self::property($yaml, 'prt_correct_html', $q->prtcorrect->text, 'string', $section);
        self::property($yaml, 'prt_partially_correct_html', $q->prtpartiallycorrect->text, 'string', $section);
        self::property($yaml, 'prt_incorrect_html', $q->prtincorrect->text, 'string', $section);


        $section = 'options';
        $yaml['options'] = array();

        $options = array(
            'sqrtsign' => 'sqrt_sign',
            'assumepos' => 'assume_positive',
            'assumereal' => 'assume_real',
            'questionsimplify' => 'simplify'
        );
        foreach ($options as $key => $value) {
            self::property($yaml['options'], $value, $q->$key, 'bool', $section);
        }

        $options = array(
            'multiplicationsign' => 'multiplication_sign',
            'complexno' => 'complex_no',
            'inversetrig' => 'inverse_trig',
            'matrixparens' => 'matrix_parens'
            );
        foreach ($options as $key => $value) {
            self::property($yaml['options'], $value, $q->$key, 'string', $section);
        }

        $this->processInputs($yaml);
        $this->processResponseTrees($yaml);

        return yaml_emit($yaml, YAML_UTF8_ENCODING);
    }

    private function getInput($input)
    {
        $section = 'input';
        $res = array();
        $this->property($res, 'type', $input->type, 'string', $section);
        $this->property($res, 'model_answer', $input->tans, 'string', $section);
        $this->property($res, 'box_size', $input->boxsize, 'int', $section);
        $this->property($res, 'insert_stars', $input->insertstars, 'int', $section);
        $this->property($res, 'strict_syntax', $input->strictsyntax, 'bool', $section);
        $this->property($res, 'syntax_hint', $input->syntaxhint, 'string', $section);
        $this->property($res, 'syntax_attribute', $input->syntaxattribute, 'string', $section);
        $this->property($res, 'forbid_words', $input->forbidwords, 'string', $section);
        $this->property($res, 'allow_words', $input->allowwords, 'string', $section);
        $this->property($res, 'forbid_float', $input->forbidfloat, 'bool', $section);
        $this->property($res, 'require_lowest_terms', $input->requirelowestterms, 'bool', $section);
        $this->property($res, 'check_answer_type', $input->checkanswertype, 'bool', $section);
        $this->property($res, 'must_verify', $input->mustverify, 'bool', $section);
        $this->property($res, 'show_validations', $input->showvalidation, 'string', $section);
        $this->property($res, 'options', $input->options, 'string', $section);

        return $res;
    }

    private function processInputs(array &$yaml)
    {
        $yaml['inputs'] = array();
        foreach ($this->question->input as $value) {
            $yaml['inputs'][(string)$value->name] = self::getInput($value);
        }
    }

    private function getResponseTreeNode( $node)
    {
        $section = 'node';
        $res = array();
        $this->property($res, 'answer_test', $node->answertest, 'string', $section);
        $this->property($res, 'quiet', $node->quiet, 'bool', $section);
        $this->property($res, 'answer', $node->sans, 'string', $section);
        $this->property($res, 'model_answer', $node->tans, 'string', $section);
        $this->property($res, 'test_options', $node->testoptions, 'string', $section);

        # true branch
        $section = 'branch-T';
        $res['T'] = array();
        $this->property($res['T'], 'score_mode', $node->truescoremode, 'string', $section);
        $this->property($res['T'], 'score', $node->truescore, 'float', $section);
        $this->property($res['T'], 'penalty', $node->truepenalty, 'float', $section);
        $next_node = ($node->truenextnode == -1)? -1 : 'node_' . (string)$node->truenextnode;
        $this->property($res['T'], 'next_node', $next_node, 'string', $section);
        $this->property($res['T'], 'answer_note', $node->trueanswernote, 'string', $section);
        $this->property($res['T'], 'feedback_html', $node->truefeedback->text, 'string', $section);
        # false branch
        $section = 'branch-F';
        $res['F'] = array();
        $this->property($res['F'], 'score_mode', $node->falsescoremode, 'string', $section);
        $this->property($res['F'], 'score', $node->falsescore, 'float', $section);
        $this->property($res['F'], 'penalty', $node->falsepenalty, 'float', $section);
        $next_node = ($node->falsenextnode == -1)? -1 : 'node_' . (string)$node->falsenextnode;

        $this->property($res['F'], 'next_node', $next_node, 'string', $section);
        $this->property($res['F'], 'answer_note', $node->falseanswernote, 'string', $section);
        $this->property($res['F'], 'feedback_html', $node->falsefeedback->text, 'string', $section);

        return $res;
    }

    private function getResponseTree($tree)
    {
        $section = 'tree';
        $res = array();
        $this->property($res, 'auto_simplify', $tree->type, 'bool', $section);
        $this->property($res, 'value', $tree->value, 'float', $section);
        $this->property($res, 'first_node', 'node_' . (int) $tree->firstnodename, 'string', $section);

        $res['nodes'] = array();
        foreach ($tree->node as $node) {
            $res['nodes']["node_" . (string) $node->name] = $this->getResponseTreeNode($node);
        }
        return $res;
    }

    private function processResponseTrees(array &$yaml)
    {
        $yaml['response_trees'] = array();
        foreach ($this->question->prt as $tree) {
            $yaml['response_trees'][(string) $tree->name] = $this->getResponseTree($tree);
        }
    }
}
