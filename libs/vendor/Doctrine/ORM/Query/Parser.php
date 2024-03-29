<?php
/*
 *  $Id$
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the LGPL. For more information, see
 * <http://www.doctrine-project.org>.
 */

namespace Doctrine\ORM\Query;

use Doctrine\Common\DoctrineException,
    Doctrine\ORM\Query;

/**
 * An LL(*) parser for the context-free grammar of the Doctrine Query Language.
 * Parses a DQL query, reports any errors in it, and generates an AST.
 *
 * @license http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link    www.doctrine-project.org
 * @since   2.0
 * @version $Revision: 3938 $
 * @author  Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @author  Jonathan Wage <jonwage@gmail.com>
 * @author  Roman Borschel <roman@code-factory.org>
 * @author  Janne Vanhala <jpvanhal@cc.hut.fi>
 */
class Parser
{
    /** Maps registered string function names to class names. */
    private static $_STRING_FUNCTIONS = array(
        'concat'    => 'Doctrine\ORM\Query\AST\Functions\ConcatFunction',
        'substring' => 'Doctrine\ORM\Query\AST\Functions\SubstringFunction',
        'trim'      => 'Doctrine\ORM\Query\AST\Functions\TrimFunction',
        'lower'     => 'Doctrine\ORM\Query\AST\Functions\LowerFunction',
        'upper'     => 'Doctrine\ORM\Query\AST\Functions\UpperFunction'
    );

    /** Maps registered numeric function names to class names. */
    private static $_NUMERIC_FUNCTIONS = array(
        'length' => 'Doctrine\ORM\Query\AST\Functions\LengthFunction',
        'locate' => 'Doctrine\ORM\Query\AST\Functions\LocateFunction',
        'abs'    => 'Doctrine\ORM\Query\AST\Functions\AbsFunction',
        'sqrt'   => 'Doctrine\ORM\Query\AST\Functions\SqrtFunction',
        'mod'    => 'Doctrine\ORM\Query\AST\Functions\ModFunction',
        'size'   => 'Doctrine\ORM\Query\AST\Functions\SizeFunction'
    );

    /** Maps registered datetime function names to class names. */
    private static $_DATETIME_FUNCTIONS = array(
        'current_date'      => 'Doctrine\ORM\Query\AST\Functions\CurrentDateFunction',
        'current_time'      => 'Doctrine\ORM\Query\AST\Functions\CurrentTimeFunction',
        'current_timestamp' => 'Doctrine\ORM\Query\AST\Functions\CurrentTimestampFunction'
    );

    /**
     * Expressions that were encountered during parsing of identifiers and expressions
     * and still need to be validated.
     *
     * @var array
     */
    private $_deferredExpressionsStack = array();

    /**
     * The lexer.
     *
     * @var Doctrine\ORM\Query\Lexer
     */
    private $_lexer;

    /**
     * The parser result.
     *
     * @var Doctrine\ORM\Query\ParserResult
     */
    private $_parserResult;

    /**
     * The EntityManager.
     *
     * @var EnityManager
     */
    private $_em;
    
    /**
     * The Query to parse.
     *
     * @var Query
     */
    private $_query;

    /**
     * Map of declared query components in the parsed query.
     *
     * @var array
     */
    private $_queryComponents = array();
    
    /**
     * Keeps the nesting level of defined ResultVariables
     *
     * @var integer
     */
    private $_nestingLevel = 0;
    
    /**
     * Any additional custom tree walkers that modify the AST.
     *
     * @var array
     */
    private $_customTreeWalkers = array();
    
    /**
     * The custom last tree walker, if any, that is responsible for producing the output.
     * 
     * @var TreeWalker
     */
    private $_customOutputWalker;

    /**
     * Creates a new query parser object.
     *
     * @param Query $query The Query to parse.
     */
    public function __construct(Query $query)
    {
        $this->_query = $query;
        $this->_em = $query->getEntityManager();
        $this->_lexer = new Lexer($query->getDql());
        $this->_parserResult = new ParserResult();
    }

    /**
     * Sets a custom tree walker that produces output.
     * This tree walker will be run last over the AST, after any other walkers.
     * 
     * @param string $className
     */
    public function setCustomOutputTreeWalker($className)
    {
        $this->_customOutputWalker = $className;
    }
    
    /**
     * Adds a custom tree walker for modifying the AST.
     * 
     * @param string $className
     */
    public function addCustomTreeWalker($className)
    {
        $this->_customTreeWalkers[] = $className;
    }

    /**
     * Gets the lexer used by the parser.
     *
     * @return Doctrine\ORM\Query\Lexer
     */
    public function getLexer()
    {
        return $this->_lexer;
    }

    /**
     * Gets the ParserResult that is being filled with information during parsing.
     *
     * @return Doctrine\ORM\Query\ParserResult
     */
    public function getParserResult()
    {
        return $this->_parserResult;
    }
    
    /**
     * Gets the EntityManager used by the parser.
     *
     * @return EntityManager
     */
    public function getEntityManager()
    {
        return $this->_em;
    }
    
    /**
     * Registers a custom function that returns strings.
     *
     * @param string $name The function name.
     * @param string $class The class name of the function implementation.
     */
    public static function registerStringFunction($name, $class)
    {
        self::$_STRING_FUNCTIONS[$name] = $class;
    }

    /**
     * Registers a custom function that returns numerics.
     *
     * @param string $name The function name.
     * @param string $class The class name of the function implementation.
     */
    public static function registerNumericFunction($name, $class)
    {
        self::$_NUMERIC_FUNCTIONS[$name] = $class;
    }

    /**
     * Registers a custom function that returns date/time values.
     *
     * @param string $name The function name.
     * @param string $class The class name of the function implementation.
     */
    public static function registerDatetimeFunction($name, $class)
    {
        self::$_DATETIME_FUNCTIONS[$name] = $class;
    }

    /**
     * Attempts to match the given token with the current lookahead token.
     *
     * If they match, updates the lookahead token; otherwise raises a syntax
     * error.
     *
     * @param int|string token type or value
     * @return bool True, if tokens match; false otherwise.
     */
    public function match($token)
    {
        if ( ! ($this->_lexer->lookahead['type'] === $token)) {
            $this->syntaxError($this->_lexer->getLiteral($token));
        }

        $this->_lexer->moveNext();
    }

    /**
     * Free this parser enabling it to be reused
     *
     * @param boolean $deep     Whether to clean peek and reset errors
     * @param integer $position Position to reset
     */
    public function free($deep = false, $position = 0)
    {
        // WARNING! Use this method with care. It resets the scanner!
        $this->_lexer->resetPosition($position);

        // Deep = true cleans peek and also any previously defined errors
        if ($deep) {
            $this->_lexer->resetPeek();
        }

        $this->_lexer->token = null;
        $this->_lexer->lookahead = null;
    }

    /**
     * Parses a query string.
     *
     * @return ParserResult
     */
    public function parse()
    {
        // Parse & build AST
        $AST = $this->QueryLanguage();
        
        // Activate semantical checks after this point. Process all deferred checks in pipeline
        $this->_processDeferredExpressionsStack($AST);
        
        if ($customWalkers = $this->_query->getHint(Query::HINT_CUSTOM_TREE_WALKERS)) {
            $this->_customTreeWalkers = $customWalkers;
        }

        // Run any custom tree walkers over the AST
        if ($this->_customTreeWalkers) {
            $treeWalkerChain = new TreeWalkerChain($this->_query, $this->_parserResult, $this->_queryComponents);
            
            foreach ($this->_customTreeWalkers as $walker) {
                $treeWalkerChain->addTreeWalker($walker);
            }
            
            if ($AST instanceof AST\SelectStatement) {
                $treeWalkerChain->walkSelectStatement($AST);
            } else if ($AST instanceof AST\UpdateStatement) {
                $treeWalkerChain->walkUpdateStatement($AST);
            } else {
                $treeWalkerChain->walkDeleteStatement($AST);
            }
        }
        
        if ($this->_customOutputWalker) {
            $outputWalker = new $this->_customOutputWalker(
                $this->_query, $this->_parserResult, $this->_queryComponents
            );
        } else {
            $outputWalker = new SqlWalker(
                $this->_query, $this->_parserResult, $this->_queryComponents
            );
        }

        // Assign an SQL executor to the parser result
        $this->_parserResult->setSqlExecutor($outputWalker->getExecutor($AST));

        return $this->_parserResult;
    }
    
    /**
     * Generates a new syntax error.
     *
     * @param string $expected Optional expected string.
     * @param array $token Optional token.
     *
     * @throws \Doctrine\ORM\Query\QueryException
     */
    public function syntaxError($expected = '', $token = null)
    {
        if ($token === null) {
            $token = $this->_lexer->lookahead;
        }

        $tokenPos = (isset($token['position'])) ? $token['position'] : '-1';
        $message  = "line 0, col {$tokenPos}: Error: ";

        if ($expected !== '') {
            $message .= "Expected {$expected}, got ";
        } else {
            $message .= 'Unexpected ';
        }

        if ($this->_lexer->lookahead === null) {
            $message .= 'end of string.';
        } else {
            $message .= "'{$token['value']}'";
        }

        throw \Doctrine\ORM\Query\QueryException::syntaxError($message);
    }

    /**
     * Generates a new semantical error.
     *
     * @param string $message Optional message.
     * @param array $token Optional token.
     *
     * @throws \Doctrine\ORM\Query\QueryException
     */
    public function semanticalError($message = '', $token = null)
    {
        if ($token === null) {
            $token = $this->_lexer->lookahead;
        }
        
        // Minimum exposed chars ahead of token
        $distance = 12;
        
        // Find a position of a final word to display in error string
        $dql = $this->_query->getDql();
        $length = strlen($dql);
        $pos = $token['position'] + $distance;
        $pos = strpos($dql, ' ', ($length > $pos) ? $pos : $length);
        $length = ($pos !== false) ? $pos - $token['position'] : $distance;
        
        // Building informative message
        $message = 'line 0, col ' . (
            (isset($token['position']) && $token['position'] > 0) ? $token['position'] : '-1'
        ) . " near '" . substr($dql, $token['position'], $length) . "': Error: " . $message;

        throw \Doctrine\ORM\Query\QueryException::semanticalError($message);
    }
    
    /**
     * Peeks beyond the specified token and returns the first token after that one.
     *
     * @param array $token
     * @return array
     */
    private function _peekBeyond($token)
    {
        $peek = $this->_lexer->peek();

        while ($peek['value'] != $token) {
            $peek = $this->_lexer->peek();
        }

        $peek = $this->_lexer->peek();
        $this->_lexer->resetPeek();

        return $peek;
    }

    /**
     * Checks if the next-next (after lookahead) token starts a function.
     *
     * @return boolean TRUE if the next-next tokens start a function, FALSE otherwise.
     */
    private function _isFunction()
    {
        $peek     = $this->_lexer->peek();
        $nextpeek = $this->_lexer->peek();
        $this->_lexer->resetPeek();
        
        // We deny the COUNT(SELECT * FROM User u) here. COUNT won't be considered a function
        return ($peek['value'] === '(' && $nextpeek['type'] !== Lexer::T_SELECT);
    }
    
    /**
     * Checks whether the function with the given name is a string function
     * (a function that returns strings).
     *
     * @return boolean TRUE if the token type is a string function, FALSE otherwise.
     */
    private function _isStringFunction($funcName)
    {
        return isset(self::$_STRING_FUNCTIONS[strtolower($funcName)]);
    }

    /**
     * Checks whether the function with the given name is a numeric function
     * (a function that returns numerics).
     *
     * @return boolean TRUE if the token type is a numeric function, FALSE otherwise.
     */
    private function _isNumericFunction($funcName)
    {
        return isset(self::$_NUMERIC_FUNCTIONS[strtolower($funcName)]);
    }

    /**
     * Checks whether the function with the given name is a datetime function
     * (a function that returns date/time values).
     *
     * @return boolean TRUE if the token type is a datetime function, FALSE otherwise.
     */
    private function _isDatetimeFunction($funcName)
    {
        return isset(self::$_DATETIME_FUNCTIONS[strtolower($funcName)]);
    }
    
    /**
     * Checks whether the given token type indicates an aggregate function.
     *
     * @return boolean TRUE if the token type is an aggregate function, FALSE otherwise.
     */
    private function _isAggregateFunction($tokenType)
    {
        return $tokenType == Lexer::T_AVG || $tokenType == Lexer::T_MIN ||
               $tokenType == Lexer::T_MAX || $tokenType == Lexer::T_SUM ||
               $tokenType == Lexer::T_COUNT;
    }

    /**
     * Checks whether the current lookahead token of the lexer has the type
     * T_ALL, T_ANY or T_SOME.
     *
     * @return boolean
     */
    private function _isNextAllAnySome()
    {
        return $this->_lexer->lookahead['type'] === Lexer::T_ALL ||
               $this->_lexer->lookahead['type'] === Lexer::T_ANY ||
               $this->_lexer->lookahead['type'] === Lexer::T_SOME;
    }

    /**
     * Checks whether the next 2 tokens start a subselect.
     *
     * @return boolean TRUE if the next 2 tokens start a subselect, FALSE otherwise.
     */
    private function _isSubselect()
    {
        $la = $this->_lexer->lookahead;
        $next = $this->_lexer->glimpse();

        return ($la['value'] === '(' && $next['type'] === Lexer::T_SELECT);
    }
    
    /**
     * Subscribe expression to be validated.
     *
     * @param mixed $expression
     * @param string $method
     * @param array $token
     * @param integer $nestingLevel
     */
    private function _subscribeExpression($expression, $method, $token, $nestingLevel = null)
    {
        $nestingLevel = ($nestingLevel !== null) ?: $this->_nestingLevel;
        
        $exprStack = array(
            'method'       => $method,
            'expression'   => $expression,
            'nestingLevel' => $nestingLevel,
            'token'        => $token,
        );
        
        array_push($this->_deferredExpressionsStack, $exprStack);
    }

    /**
     * Processes the topmost stack of deferred path expressions.
     *
     * @param mixed $AST
     */
    private function _processDeferredExpressionsStack($AST)
    {
        foreach ($this->_deferredExpressionsStack as $item) {
            $method = '_validate' . $item['method'];
            
            $this->$method($item, $AST);
        }
    }
    
    
    /**
     * Validates that the given <tt>IdentificationVariable</tt> is a semantically correct. 
     * It must exist in query components list.
     *
     * @param array $deferredItem
     * @param mixed $AST
     *
     * @return array Query Component
     */
    private function _validateIdentificationVariable($deferredItem, $AST)
    {
        $identVariable = $deferredItem['expression'];
    
        // Check if IdentificationVariable exists in queryComponents
        if ( ! isset($this->_queryComponents[$identVariable])) {
            $this->semanticalError(
                "'$identVariable' is not defined.", $deferredItem['token']
            );
        }
        
        $qComp = $this->_queryComponents[$identVariable];
        
        // Check if queryComponent points to an AbstractSchemaName or a ResultVariable
        if ( ! isset($qComp['metadata'])) {
            $this->semanticalError(
                "'$identVariable' does not point to a Class.", $deferredItem['token']
            );
        }
        
        // Validate if identification variable nesting level is lower or equal than the current one
        if ($qComp['nestingLevel'] > $deferredItem['nestingLevel']) {
            $this->semanticalError(
                "'$identVariable' is used outside the scope of its declaration.", $deferredItem['token']
            );
        }
        
        return $qComp;
    }
    
    /**
     * Validates that the given <tt>ResultVariable</tt> is a semantically correct. 
     * It must exist in query components list.
     *
     * @param array $deferredItem
     * @param mixed $AST
     *
     * @return array Query Component
     */
    private function _validateResultVariable($deferredItem, $AST)
    {
        $resultVariable = $deferredItem['expression'];
        
        // Check if ResultVariable exists in queryComponents
        if ( ! isset($this->_queryComponents[$resultVariable])) {
            $this->semanticalError(
                "'$resultVariable' is not defined.", $deferredItem['token']
            );
        }
        
        $qComp = $this->_queryComponents[$resultVariable];
        
        // Check if queryComponent points to an AbstractSchemaName or a ResultVariable
        if ( ! isset($qComp['resultVariable'])) {
            $this->semanticalError(
                "'$identVariable' does not point to a ResultVariable.", $deferredItem['token']
            );
        }
        
        // Validate if identification variable nesting level is lower or equal than the current one
        if ($qComp['nestingLevel'] > $deferredItem['nestingLevel']) {
            $this->semanticalError(
                "'$resultVariable' is used outside the scope of its declaration.", $deferredItem['token']
            );
        }
        
        return $qComp;
    }
    
    /**
     * Validates that the given <tt>JoinAssociationPathExpression</tt> is a semantically correct. 
     *
     * @param array $deferredItem
     * @param mixed $AST
     *
     * @return array Query Component
     */
    private function _validateJoinAssociationPathExpression($deferredItem, $AST)
    {
        $pathExpression = $deferredItem['expression'];
        $qComp = $this->_queryComponents[$pathExpression->identificationVariable];;
        
        // Validating association field (*-to-one or *-to-many)
        $field = $pathExpression->associationField;
        $class = $qComp['metadata'];
        
        if ( ! isset($class->associationMappings[$field])) {
            $this->semanticalError('Class ' . $class->name . ' has no association named ' . $field);
        }
        
        return $qComp;
    }
    
    /**
     * Validates that the given <tt>PathExpression</tt> is a semantically correct for grammar rules:
     *
     * AssociationPathExpression             ::= CollectionValuedPathExpression | SingleValuedAssociationPathExpression
     * SingleValuedPathExpression            ::= StateFieldPathExpression | SingleValuedAssociationPathExpression
     * StateFieldPathExpression              ::= IdentificationVariable "." StateField | SingleValuedAssociationPathExpression "." StateField
     * SingleValuedAssociationPathExpression ::= IdentificationVariable "." {SingleValuedAssociationField "."}* SingleValuedAssociationField
     * CollectionValuedPathExpression        ::= IdentificationVariable "." {SingleValuedAssociationField "."}* CollectionValuedAssociationField
     *
     * @param array $deferredItem
     * @param mixed $AST
     *
     * @return integer
     */
    private function _validatePathExpression($deferredItem, $AST)
    {
        $pathExpression = $deferredItem['expression'];
        $parts = $pathExpression->parts;
        $numParts = count($parts);
        
        $qComp = $this->_queryComponents[$pathExpression->identificationVariable];
            
        $aliasIdentificationVariable = $pathExpression->identificationVariable;
        $parentField = $pathExpression->identificationVariable;
        $class = $qComp['metadata'];
        $fieldType = null;
        $curIndex = 0;
            
        foreach ($parts as $field) {
            // Check if it is not in a state field
            if ($fieldType & AST\PathExpression::TYPE_STATE_FIELD) {
                $this->semanticalError(
                    'Cannot navigate through state field named ' . $field . ' on ' . $parentField, 
                    $deferredItem['token']
                );
            }
            
            // Check if it is not a collection field
            if ($fieldType & AST\PathExpression::TYPE_COLLECTION_VALUED_ASSOCIATION) {
                $this->semanticalError(
                    'Cannot navigate through collection field named ' . $field . ' on ' . $parentField, 
                    $deferredItem['token']
                );
            }
            
            // Check if field or association exists
            if ( ! isset($class->associationMappings[$field]) && ! isset($class->fieldMappings[$field])) {
                $this->semanticalError(
                    'Class ' . $class->name . ' has no field or association named ' . $field, 
                    $deferredItem['token']
                );
            }
                
            $parentField = $field;
            
            if (isset($class->fieldMappings[$field])) {
                $fieldType = AST\PathExpression::TYPE_STATE_FIELD;
            } else {
                $assoc = $class->associationMappings[$field];
                $class = $this->_em->getClassMetadata($assoc->targetEntityName);

                if (
                    ($curIndex != $numParts - 1) &&
                    ! isset($this->_queryComponents[$aliasIdentificationVariable . '.' . $field])
                ) {
                    // Building queryComponent
                    $joinQueryComponent = array(
                        'metadata'     => $class,
                        'parent'       => $aliasIdentificationVariable,
                        'relation'     => $assoc,
                        'map'          => null,
                        'nestingLevel' => $this->_nestingLevel,
                        'token'        => $deferredItem['token'],
                    );

                    // Create AST node
                    $joinVariableDeclaration = new AST\JoinVariableDeclaration(
                        new AST\Join(
                            AST\Join::JOIN_TYPE_INNER, 
                            new AST\JoinAssociationPathExpression($aliasIdentificationVariable, $field), 
                            $aliasIdentificationVariable . '.' . $field
                        ),
                        null
                    );
                    $AST->fromClause->identificationVariableDeclarations[0]->joinVariableDeclarations[] = $joinVariableDeclaration;
                    
                    $this->_queryComponents[$aliasIdentificationVariable . '.' . $field] = $joinQueryComponent;
                }
                    
                $aliasIdentificationVariable .= '.' . $field;
                    
                if ($assoc->isOneToOne()) {
                    $fieldType = AST\PathExpression::TYPE_SINGLE_VALUED_ASSOCIATION;
                } else {
                    $fieldType = AST\PathExpression::TYPE_COLLECTION_VALUED_ASSOCIATION;
                }
            }
            
            $curIndex++;
        }
        
        // Validate if PathExpression is one of the expected types
        $expectedType = $pathExpression->expectedType;

        if ( ! ($expectedType & $fieldType)) {
            // We need to recognize which was expected type(s)
            $expectedStringTypes = array();
				
            // Validate state field type
            if ($expectedType & AST\PathExpression::TYPE_STATE_FIELD) {
                $expectedStringTypes[] = 'StateFieldPathExpression';
            }
                
            // Validate single valued association (*-to-one)
            if ($expectedType & AST\PathExpression::TYPE_SINGLE_VALUED_ASSOCIATION) {
                $expectedStringTypes[] = 'SingleValuedAssociationField';
            }
                
            // Validate single valued association (*-to-many)
            if ($expectedType & AST\PathExpression::TYPE_COLLECTION_VALUED_ASSOCIATION) {
                $expectedStringTypes[] = 'CollectionValuedAssociationField';
            }
                
            // Build the error message
            $semanticalError = 'Invalid PathExpression. ';
            
            if (count($expectedStringTypes) == 1) {
                $semanticalError .= 'Must be a ' . $expectedStringTypes[0] . '.';
            } else {
                $semanticalError .= implode(' or ', $expectedStringTypes) . ' expected.';
            }
            
            $this->semanticalError($semanticalError, $deferredItem['token']);
        }
        
        // We need to force the type in PathExpression
        $pathExpression->type = $fieldType;
        
        return $fieldType;
    }
    
    /**
     * QueryLanguage ::= SelectStatement | UpdateStatement | DeleteStatement
     *
     * @return \Doctrine\ORM\Query\AST\SelectStatement | 
     *         \Doctrine\ORM\Query\AST\UpdateStatement | 
     *         \Doctrine\ORM\Query\AST\DeleteStatement
     */
    public function QueryLanguage()
    {
        $this->_lexer->moveNext();
        
        switch ($this->_lexer->lookahead['type']) {
            case Lexer::T_SELECT:
                return $this->SelectStatement();

            case Lexer::T_UPDATE:
                return $this->UpdateStatement();

            case Lexer::T_DELETE:
                return $this->DeleteStatement();

            default:
                $this->syntaxError('SELECT, UPDATE or DELETE');
                break;
        }
        
        // Check for end of string
        if ($this->_lexer->lookahead !== null) {
            $this->syntaxError('end of string');
        }
    }
    

    /**
     * SelectStatement ::= SelectClause FromClause [WhereClause] [GroupByClause] [HavingClause] [OrderByClause]
     *
     * @return \Doctrine\ORM\Query\AST\SelectStatement
     */
    public function SelectStatement()
    {
        $selectStatement = new AST\SelectStatement($this->SelectClause(), $this->FromClause());
        
        $selectStatement->whereClause = $this->_lexer->isNextToken(Lexer::T_WHERE)
            ? $this->WhereClause() : null;

        $selectStatement->groupByClause = $this->_lexer->isNextToken(Lexer::T_GROUP)
            ? $this->GroupByClause() : null;

        $selectStatement->havingClause = $this->_lexer->isNextToken(Lexer::T_HAVING)
            ? $this->HavingClause() : null;

        $selectStatement->orderByClause = $this->_lexer->isNextToken(Lexer::T_ORDER)
            ? $this->OrderByClause() : null;
            
        return $selectStatement;
    }

    /**
     * UpdateStatement ::= UpdateClause [WhereClause]
     *
     * @return \Doctrine\ORM\Query\AST\UpdateStatement
     */
    public function UpdateStatement()
    {
        $updateStatement = new AST\UpdateStatement($this->UpdateClause());
        $updateStatement->whereClause = $this->_lexer->isNextToken(Lexer::T_WHERE) 
            ? $this->WhereClause() : null;

        return $updateStatement;
    }
    
    /**
     * DeleteStatement ::= DeleteClause [WhereClause]
     *
     * @return \Doctrine\ORM\Query\AST\DeleteStatement
     */
    public function DeleteStatement()
    {
        $deleteStatement = new AST\DeleteStatement($this->DeleteClause());
        $deleteStatement->whereClause = $this->_lexer->isNextToken(Lexer::T_WHERE) 
            ? $this->WhereClause() : null;

        return $deleteStatement;
    }
    
    
    /**
     * IdentificationVariable ::= identifier
     *
     * @return string
     */
    public function IdentificationVariable()
    {
        $this->match(Lexer::T_IDENTIFIER);

        $identVariable = $this->_lexer->token['value'];
        
        // Defer IdentificationVariable validation
        $exprStack = array(
            'method'       => 'IdentificationVariable',
            'expression'   => $identVariable,
            'nestingLevel' => $this->_nestingLevel,
            'token'        => $this->_lexer->token,
        );

        array_push($this->_deferredExpressionsStack, $exprStack);

        return $identVariable;
    }
    
    /**
     * AliasIdentificationVariable = identifier
     *
     * @return string
     */
    public function AliasIdentificationVariable()
    {
        $this->match(Lexer::T_IDENTIFIER);
        
        $aliasIdentVariable = $this->_lexer->token['value'];
        $exists = isset($this->_queryComponents[$aliasIdentVariable]);
        
        if ($exists) {
            $this->semanticalError(
                "'$aliasIdentVariable' is already defined.", $this->_lexer->token
            );
        }

        return $aliasIdentVariable;
    }
    
    /**
     * AbstractSchemaName ::= identifier
     *
     * @return string
     */
    public function AbstractSchemaName()
    {
        $this->match(Lexer::T_IDENTIFIER);

        $schemaName = $this->_lexer->token['value'];
        $exists = class_exists($schemaName, true);
        
        if ( ! $exists) {
            $this->semanticalError("Class '$schemaName' is not defined.", $this->_lexer->token);
        }

        return $schemaName;
    }
    
    /**
     * AliasResultVariable ::= identifier
     *
     * @return string
     */
    public function AliasResultVariable()
    {
        $this->match(Lexer::T_IDENTIFIER);
    
        $resultVariable = $this->_lexer->token['value'];
        $exists = isset($this->_queryComponents[$resultVariable]);
        
        if ($exists) {
            $this->semanticalError(
                "'$resultVariable' is already defined.", $this->_lexer->token
            );
        }
        
        return $resultVariable;
    }
    
    /**
     * ResultVariable ::= identifier
     *
     * @return string
     */
    public function ResultVariable()
    {
        $this->match(Lexer::T_IDENTIFIER);
    
        $resultVariable = $this->_lexer->token['value'];
        
        // Defer ResultVariable validation
        $this->_subscribeExpression(
            $resultVariable, 'ResultVariable',
            $this->_lexer->token, $this->_nestingLevel
        );
        
        return $resultVariable;
    }
    

    /**
     * JoinAssociationPathExpression ::= IdentificationVariable "." (CollectionValuedAssociationField | SingleValuedAssociationField)
     *
     * @return \Doctrine\ORM\Query\AST\JoinAssociationPathExpression
     */
    public function JoinAssociationPathExpression()
    {
        $token = $this->_lexer->lookahead;
        
        $identVariable = $this->IdentificationVariable();
        $this->match(Lexer::T_DOT);
        $this->match(Lexer::T_IDENTIFIER);
        $field = $this->_lexer->token['value'];
        
        $pathExpr = new AST\JoinAssociationPathExpression($identVariable, $field);
        
        // Defer JoinAssociationPathExpression validation
        $this->_subscribeExpression(
            $pathExpr, 'JoinAssociationPathExpression',
            $token, $this->_nestingLevel
        );
        
        return $pathExpr;
    }  

    /**
     * Parses an arbitrary path expression and defers semantical validation 
     * based on expected types.
     *
     * PathExpression ::= IdentificationVariable {"." identifier}* "." identifier
     *
     * @param integer $expectedTypes
     * @return \Doctrine\ORM\Query\AST\PathExpression
     */
    public function PathExpression($expectedTypes)
    {
        $token = $this->_lexer->lookahead;
        $identVariable = $this->IdentificationVariable();
        $parts = array();

        do {
            $this->match(Lexer::T_DOT);
            $this->match(Lexer::T_IDENTIFIER);
            
            $parts[] = $this->_lexer->token['value'];
        } while ($this->_lexer->isNextToken(Lexer::T_DOT));
        
        // Creating AST node
        $pathExpr = new AST\PathExpression($expectedTypes, $identVariable, $parts);
        
        // Defer PathExpression validation if requested to be defered
        $this->_subscribeExpression(
            $pathExpr, 'PathExpression',
            $token, $this->_nestingLevel
        );

        return $pathExpr;
    }
    
    /**
     * AssociationPathExpression ::= CollectionValuedPathExpression | SingleValuedAssociationPathExpression
     *
     * @return \Doctrine\ORM\Query\AST\PathExpression
     */
    public function AssociationPathExpression()
    {
        return $this->PathExpression(
            AST\PathExpression::TYPE_SINGLE_VALUED_ASSOCIATION |
            AST\PathExpression::TYPE_COLLECTION_VALUED_ASSOCIATION
        );
    }
    
    /**
     * SingleValuedPathExpression ::= StateFieldPathExpression | SingleValuedAssociationPathExpression
     *
     * @return \Doctrine\ORM\Query\AST\PathExpression
     */
    public function SingleValuedPathExpression()
    {
        return $this->PathExpression(
            AST\PathExpression::TYPE_STATE_FIELD |
            AST\PathExpression::TYPE_SINGLE_VALUED_ASSOCIATION
        );
    }
    
    /**
     * StateFieldPathExpression ::= SimpleStateFieldPathExpression | SimpleStateFieldAssociationPathExpression
     *
     * @return \Doctrine\ORM\Query\AST\PathExpression
     */
    public function StateFieldPathExpression()
    {
        return $this->PathExpression(AST\PathExpression::TYPE_STATE_FIELD);
    }
    
    /**
     * SingleValuedAssociationPathExpression ::= IdentificationVariable "." {SingleValuedAssociationField "."}* SingleValuedAssociationField
     *
     * @return \Doctrine\ORM\Query\AST\PathExpression
     */
    public function SingleValuedAssociationPathExpression()
    {
        return $this->PathExpression(AST\PathExpression::TYPE_SINGLE_VALUED_ASSOCIATION);
    }
    
    /**
     * CollectionValuedPathExpression ::= IdentificationVariable "." {SingleValuedAssociationField "."}* CollectionValuedAssociationField
     *
     * @return \Doctrine\ORM\Query\AST\PathExpression
     */
    public function CollectionValuedPathExpression()
    {
        return $this->PathExpression(AST\PathExpression::TYPE_COLLECTION_VALUED_ASSOCIATION);
    }
    
    /**
     * SimpleStateFieldPathExpression ::= IdentificationVariable "." StateField
     *
     * @return \Doctrine\ORM\Query\AST\PathExpression
     */
    public function SimpleStateFieldPathExpression()
    {
        $pathExpression = $this->PathExpression(AST\PathExpression::TYPE_STATE_FIELD);
        $parts = $pathExpression->parts;
        
        if (count($parts) > 1) {
            $this->semanticalError(
                "Invalid SimpleStateFieldPathExpression. " . 
                "Expected state field, got association '{$parts[0]}'."
            );
        }
        
        return $pathExpression;
    }

    
    /**
     * SelectClause ::= "SELECT" ["DISTINCT"] SelectExpression {"," SelectExpression}
     *
     * @return \Doctrine\ORM\Query\AST\SelectClause
     */
    public function SelectClause()
    {
        $isDistinct = false;
        $this->match(Lexer::T_SELECT);

        // Check for DISTINCT
        if ($this->_lexer->isNextToken(Lexer::T_DISTINCT)) {
            $this->match(Lexer::T_DISTINCT);
            $isDistinct = true;
        }

        // Process SelectExpressions (1..N)
        $selectExpressions = array();
        $selectExpressions[] = $this->SelectExpression();

        while ($this->_lexer->isNextToken(Lexer::T_COMMA)) {
            $this->match(Lexer::T_COMMA);
            $selectExpressions[] = $this->SelectExpression();
        }

        return new AST\SelectClause($selectExpressions, $isDistinct);
    }

    /**
     * SimpleSelectClause ::= "SELECT" ["DISTINCT"] SimpleSelectExpression
     *
     * @return \Doctrine\ORM\Query\AST\SimpleSelectClause
     */
    public function SimpleSelectClause()
    {
        $isDistinct = false;
        $this->match(Lexer::T_SELECT);

        if ($this->_lexer->isNextToken(Lexer::T_DISTINCT)) {
            $this->match(Lexer::T_DISTINCT);
            $isDistinct = true;
        }

        return new AST\SimpleSelectClause($this->SimpleSelectExpression(), $isDistinct);
    }

    /**
     * UpdateClause ::= "UPDATE" AbstractSchemaName ["AS"] AliasIdentificationVariable "SET" UpdateItem {"," UpdateItem}*
     *
     * @return \Doctrine\ORM\Query\AST\UpdateClause
     */
    public function UpdateClause()
    {
        $this->match(Lexer::T_UPDATE);
        $token = $this->_lexer->lookahead;
        $abstractSchemaName = $this->AbstractSchemaName();
        
        if ($this->_lexer->isNextToken(Lexer::T_AS)) {
            $this->match(Lexer::T_AS);
        }

        $aliasIdentificationVariable = $this->AliasIdentificationVariable();
        
        $class = $this->_em->getClassMetadata($abstractSchemaName);

        // Building queryComponent
        $queryComponent = array(
            'metadata'     => $class,
            'parent'       => null,
            'relation'     => null,
            'map'          => null,
            'nestingLevel' => $this->_nestingLevel,
            'token'        => $token,
        );
        $this->_queryComponents[$aliasIdentificationVariable] = $queryComponent;

        $this->match(Lexer::T_SET);
        
        $updateItems = array();
        $updateItems[] = $this->UpdateItem();

        while ($this->_lexer->isNextToken(Lexer::T_COMMA)) {
            $this->match(Lexer::T_COMMA);
            $updateItems[] = $this->UpdateItem();
        }

        $updateClause = new AST\UpdateClause($abstractSchemaName, $updateItems);
        $updateClause->aliasIdentificationVariable = $aliasIdentificationVariable;

        return $updateClause;
    }

    /**
     * DeleteClause ::= "DELETE" ["FROM"] AbstractSchemaName ["AS"] AliasIdentificationVariable
     *
     * @return \Doctrine\ORM\Query\AST\DeleteClause
     */
    public function DeleteClause()
    {
        $this->match(Lexer::T_DELETE);

        if ($this->_lexer->isNextToken(Lexer::T_FROM)) {
            $this->match(Lexer::T_FROM);
        }

        $token = $this->_lexer->lookahead;
        $deleteClause = new AST\DeleteClause($this->AbstractSchemaName());
        
        if ($this->_lexer->isNextToken(Lexer::T_AS)) {
            $this->match(Lexer::T_AS);
        }

        $aliasIdentificationVariable = $this->AliasIdentificationVariable();
        
        $deleteClause->aliasIdentificationVariable = $aliasIdentificationVariable;
        $class = $this->_em->getClassMetadata($deleteClause->abstractSchemaName);
        
        // Building queryComponent
        $queryComponent = array(
            'metadata'     => $class,
            'parent'       => null,
            'relation'     => null,
            'map'          => null,
            'nestingLevel' => $this->_nestingLevel,
            'token'        => $token,
        );
        $this->_queryComponents[$aliasIdentificationVariable] = $queryComponent;

        return $deleteClause;
    }

    /**
     * FromClause ::= "FROM" IdentificationVariableDeclaration {"," IdentificationVariableDeclaration}*
     *
     * @return \Doctrine\ORM\Query\AST\FromClause
     */
    public function FromClause()
    {
        $this->match(Lexer::T_FROM);
        $identificationVariableDeclarations = array();
        $identificationVariableDeclarations[] = $this->IdentificationVariableDeclaration();

        while ($this->_lexer->isNextToken(Lexer::T_COMMA)) {
            $this->match(Lexer::T_COMMA);
            $identificationVariableDeclarations[] = $this->IdentificationVariableDeclaration();
        }

        return new AST\FromClause($identificationVariableDeclarations);
    }

    /**
     * SubselectFromClause ::= "FROM" SubselectIdentificationVariableDeclaration {"," SubselectIdentificationVariableDeclaration}*
     *
     * @return \Doctrine\ORM\Query\AST\SubselectFromClause
     */
    public function SubselectFromClause()
    {
        $this->match(Lexer::T_FROM);
        $identificationVariables = array();
        $identificationVariables[] = $this->SubselectIdentificationVariableDeclaration();

        while ($this->_lexer->isNextToken(Lexer::T_COMMA)) {
            $this->match(Lexer::T_COMMA);
            $identificationVariables[] = $this->SubselectIdentificationVariableDeclaration();
        }

        return new AST\SubselectFromClause($identificationVariables);
    }

    /**
     * WhereClause ::= "WHERE" ConditionalExpression
     *
     * @return \Doctrine\ORM\Query\AST\WhereClause
     */
    public function WhereClause()
    {
        $this->match(Lexer::T_WHERE);

        return new AST\WhereClause($this->ConditionalExpression());
    }

    /**
     * HavingClause ::= "HAVING" ConditionalExpression
     *
     * @return \Doctrine\ORM\Query\AST\HavingClause
     */
    public function HavingClause()
    {
        $this->match(Lexer::T_HAVING);

        return new AST\HavingClause($this->ConditionalExpression());
    }

    /**
     * GroupByClause ::= "GROUP" "BY" GroupByItem {"," GroupByItem}*
     *
     * @return \Doctrine\ORM\Query\AST\GroupByClause
     */
    public function GroupByClause()
    {
        $this->match(Lexer::T_GROUP);
        $this->match(Lexer::T_BY);

        $groupByItems = array($this->GroupByItem());

        while ($this->_lexer->isNextToken(Lexer::T_COMMA)) {
            $this->match(Lexer::T_COMMA);
            $groupByItems[] = $this->GroupByItem();
        }

        return new AST\GroupByClause($groupByItems);
    }
    
    /**
     * OrderByClause ::= "ORDER" "BY" OrderByItem {"," OrderByItem}*
     *
     * @return \Doctrine\ORM\Query\AST\OrderByClause
     */
    public function OrderByClause()
    {
        $this->match(Lexer::T_ORDER);
        $this->match(Lexer::T_BY);

        $orderByItems = array();
        $orderByItems[] = $this->OrderByItem();

        while ($this->_lexer->isNextToken(Lexer::T_COMMA)) {
            $this->match(Lexer::T_COMMA);
            $orderByItems[] = $this->OrderByItem();
        }

        return new AST\OrderByClause($orderByItems);
    }

    /**
     * Subselect ::= SimpleSelectClause SubselectFromClause [WhereClause] [GroupByClause] [HavingClause] [OrderByClause]
     *
     * @return \Doctrine\ORM\Query\AST\Subselect
     */
    public function Subselect()
    {
        // Increase query nesting level
        $this->_nestingLevel++;
        
        $subselect = new AST\Subselect($this->SimpleSelectClause(), $this->SubselectFromClause());
        
        $subselect->whereClause = $this->_lexer->isNextToken(Lexer::T_WHERE) 
            ? $this->WhereClause() : null;
            
        $subselect->groupByClause = $this->_lexer->isNextToken(Lexer::T_GROUP) 
            ? $this->GroupByClause() : null;
            
        $subselect->havingClause = $this->_lexer->isNextToken(Lexer::T_HAVING) 
            ? $this->HavingClause() : null;
            
        $subselect->orderByClause = $this->_lexer->isNextToken(Lexer::T_ORDER) 
            ? $this->OrderByClause() : null;
            
        // Decrease query nesting level
        $this->_nestingLevel--;

        return $subselect;
    }

    
    /**
     * UpdateItem ::= IdentificationVariable "." {StateField | SingleValuedAssociationField} "=" NewValue
     *
     * @return \Doctrine\ORM\Query\AST\UpdateItem
     */
    public function UpdateItem()
    {
        $token = $this->_lexer->lookahead;
        
        $identVariable = $this->IdentificationVariable();
        $this->match(Lexer::T_DOT);
        $this->match(Lexer::T_IDENTIFIER);
        $field = $this->_lexer->token['value'];
        
        // Check if field exists
        $class = $this->_queryComponents[$identVariable]['metadata'];
        
        if ( ! isset($class->associationMappings[$field]) && ! isset($class->fieldMappings[$field])) {
            $this->semanticalError(
                'Class ' . $class->name . ' has no field named ' . $field, $token
            );
        }
        
        $this->match(Lexer::T_EQUALS);
        
        $newValue = $this->NewValue();

        $updateItem = new AST\UpdateItem($field, $newValue);
        $updateItem->identificationVariable = $identVariable;

        return $updateItem;
    }

    /**
     * GroupByItem ::= IdentificationVariable | SingleValuedPathExpression
     *
     * @return string | \Doctrine\ORM\Query\AST\PathExpression
     */
    public function GroupByItem()
    {
        // We need to check if we are in a IdentificationVariable or SingleValuedPathExpression
        $glimpse = $this->_lexer->glimpse();
        
        if ($glimpse['value'] != '.') {
            $token = $this->_lexer->lookahead;
            $identVariable = $this->IdentificationVariable();
            
            // Validate if IdentificationVariable is defined
            $this->_validateIdentificationVariable($identVariable, null, $token);
            
            return $identVariable;
        }
        
        return $this->SingleValuedPathExpression();
    }

    /**
     * OrderByItem ::= (ResultVariable | StateFieldPathExpression) ["ASC" | "DESC"]
     *
     * @todo Post 2.0 release. Support general SingleValuedPathExpression instead 
     * of only StateFieldPathExpression.
     *
     * @return \Doctrine\ORM\Query\AST\OrderByItem
     */
    public function OrderByItem()
    {
        $type = 'ASC';
        
        // We need to check if we are in a ResultVariable or StateFieldPathExpression
        $glimpse = $this->_lexer->glimpse();
        
        if ($glimpse['value'] != '.') {
            $token = $this->_lexer->lookahead;
            $expr = $this->ResultVariable();
        } else {
            $expr = $this->StateFieldPathExpression();
        }
    
        $item = new AST\OrderByItem($expr);

        if ($this->_lexer->isNextToken(Lexer::T_ASC)) {
            $this->match(Lexer::T_ASC);
        } else if ($this->_lexer->isNextToken(Lexer::T_DESC)) {
            $this->match(Lexer::T_DESC);
            $type = 'DESC';
        }
        
        $item->type = $type;
        return $item;
    }

    /**
     * NewValue ::= SimpleArithmeticExpression | StringPrimary | DatetimePrimary | BooleanPrimary |
     *      EnumPrimary | SimpleEntityExpression | "NULL"
     *
     * NOTE: Since it is not possible to correctly recognize individual types, here is the full
     * grammar that needs to be supported:
     * 
     * NewValue ::= SimpleArithmeticExpression | "NULL"
     *
     * SimpleArithmeticExpression covers all *Primary grammar rules and also SimplEntityExpression
     */
    public function NewValue()
    {
        if ($this->_lexer->isNextToken(Lexer::T_NULL)) {
            $this->match(Lexer::T_NULL);
            return null;
        } else if ($this->_lexer->isNextToken(Lexer::T_INPUT_PARAMETER)) {
            $this->match(Lexer::T_INPUT_PARAMETER);
            return new AST\InputParameter($this->_lexer->token['value']);
        }
        
        return $this->SimpleArithmeticExpression();
    }

    
    /**
     * IdentificationVariableDeclaration ::= RangeVariableDeclaration [IndexBy] {JoinVariableDeclaration}*
     *
     * @return \Doctrine\ORM\Query\AST\IdentificationVariableDeclaration
     */
    public function IdentificationVariableDeclaration()
    {
        $rangeVariableDeclaration = $this->RangeVariableDeclaration();
        $indexBy = $this->_lexer->isNextToken(Lexer::T_INDEX) ? $this->IndexBy() : null;
        $joinVariableDeclarations = array();

        while (
            $this->_lexer->isNextToken(Lexer::T_LEFT) ||
            $this->_lexer->isNextToken(Lexer::T_INNER) ||
            $this->_lexer->isNextToken(Lexer::T_JOIN)
        ) {
            $joinVariableDeclarations[] = $this->JoinVariableDeclaration();
        }

        return new AST\IdentificationVariableDeclaration(
            $rangeVariableDeclaration, $indexBy, $joinVariableDeclarations
        );
    }

    /**
     * SubselectIdentificationVariableDeclaration ::= IdentificationVariableDeclaration | (AssociationPathExpression ["AS"] AliasIdentificationVariable)
     *
     * @return \Doctrine\ORM\Query\AST\SubselectIdentificationVariableDeclaration |
     *         \Doctrine\ORM\Query\AST\IdentificationVariableDeclaration
     */
    public function SubselectIdentificationVariableDeclaration()
    {
        $peek = $this->_lexer->glimpse();

        if ($peek['value'] == '.') {
            $subselectIdVarDecl = new AST\SubselectIdentificationVariableDeclaration();
            $subselectIdVarDecl->associationPathExpression = $this->AssociationPathExpression();
            $this->match(Lexer::T_AS);
            $subselectIdVarDecl->aliasIdentificationVariable = $this->AliasIdentificationVariable();

            return $subselectIdVarDecl;
        }

        return $this->IdentificationVariableDeclaration();
    }

    /**
     * JoinVariableDeclaration ::= Join [IndexBy]
     *
     * @return \Doctrine\ORM\Query\AST\JoinVariableDeclaration
     */
    public function JoinVariableDeclaration()
    {
        $join = $this->Join();
        $indexBy = $this->_lexer->isNextToken(Lexer::T_INDEX)
            ? $this->IndexBy() : null;

        return new AST\JoinVariableDeclaration($join, $indexBy);
    }

    /**
     * RangeVariableDeclaration ::= AbstractSchemaName ["AS"] AliasIdentificationVariable
     *
     * @return \Doctrine\ORM\Query\AST\RangeVariableDeclaration
     */
    public function RangeVariableDeclaration()
    {
        $abstractSchemaName = $this->AbstractSchemaName();

        if ($this->_lexer->isNextToken(Lexer::T_AS)) {
            $this->match(Lexer::T_AS);
        }

        $token = $this->_lexer->lookahead;
        $aliasIdentificationVariable = $this->AliasIdentificationVariable();
        $classMetadata = $this->_em->getClassMetadata($abstractSchemaName);

        // Building queryComponent
        $queryComponent = array(
            'metadata'     => $classMetadata,
            'parent'       => null,
            'relation'     => null,
            'map'          => null,
            'nestingLevel' => $this->_nestingLevel,
            'token'        => $token,
        );
        $this->_queryComponents[$aliasIdentificationVariable] = $queryComponent;

        return new AST\RangeVariableDeclaration($abstractSchemaName, $aliasIdentificationVariable);
    }

    /**
     * Join ::= ["LEFT" ["OUTER"] | "INNER"] "JOIN" JoinAssociationPathExpression
     *          ["AS"] AliasIdentificationVariable [("ON" | "WITH") ConditionalExpression]
     *
     * @return \Doctrine\ORM\Query\AST\Join
     */
    public function Join()
    {
        // Check Join type
        $joinType = AST\Join::JOIN_TYPE_INNER;

        if ($this->_lexer->isNextToken(Lexer::T_LEFT)) {
            $this->match(Lexer::T_LEFT);

            // Possible LEFT OUTER join
            if ($this->_lexer->isNextToken(Lexer::T_OUTER)) {
                $this->match(Lexer::T_OUTER);
                $joinType = AST\Join::JOIN_TYPE_LEFTOUTER;
            } else {
                $joinType = AST\Join::JOIN_TYPE_LEFT;
            }
        } else if ($this->_lexer->isNextToken(Lexer::T_INNER)) {
            $this->match(Lexer::T_INNER);
        }

        $this->match(Lexer::T_JOIN);
        
        $joinPathExpression = $this->JoinAssociationPathExpression();

        if ($this->_lexer->isNextToken(Lexer::T_AS)) {
            $this->match(Lexer::T_AS);
        }

        $token = $this->_lexer->lookahead;
        $aliasIdentificationVariable = $this->AliasIdentificationVariable();

        // Verify that the association exists.
        $parentClass = $this->_queryComponents[$joinPathExpression->identificationVariable]['metadata'];
        $assocField = $joinPathExpression->associationField;

        if ( ! $parentClass->hasAssociation($assocField)) {
            $this->semanticalError(
                "Class " . $parentClass->name . " has no association named '$assocField'."
            );
        }

        $targetClassName = $parentClass->getAssociationMapping($assocField)->targetEntityName;

        // Building queryComponent
        $joinQueryComponent = array(
            'metadata'     => $this->_em->getClassMetadata($targetClassName),
            'parent'       => $joinPathExpression->identificationVariable,
            'relation'     => $parentClass->getAssociationMapping($assocField),
            'map'          => null,
            'nestingLevel' => $this->_nestingLevel,
            'token'        => $token,
        );
        $this->_queryComponents[$aliasIdentificationVariable] = $joinQueryComponent;

        // Create AST node
        $join = new AST\Join($joinType, $joinPathExpression, $aliasIdentificationVariable);

        // Check for ad-hoc Join conditions
        if ($this->_lexer->isNextToken(Lexer::T_ON) || $this->_lexer->isNextToken(Lexer::T_WITH)) {
            if ($this->_lexer->isNextToken(Lexer::T_ON)) {
                $this->match(Lexer::T_ON);
                $join->whereType = AST\Join::JOIN_WHERE_ON;
            } else {
                $this->match(Lexer::T_WITH);
            }

            $join->conditionalExpression = $this->ConditionalExpression();
        }

        return $join;
    }

    /**
     * IndexBy ::= "INDEX" "BY" SimpleStateFieldPathExpression
     *
     * @return \Doctrine\ORM\Query\AST\IndexBy
     */
    public function IndexBy()
    {
        $this->match(Lexer::T_INDEX);
        $this->match(Lexer::T_BY);
        $pathExp = $this->SimpleStateFieldPathExpression();

        // Add the INDEX BY info to the query component
        $parts = $pathExp->parts;
        $this->_queryComponents[$pathExp->identificationVariable]['map'] = $parts[0];

        return $pathExp;
    }

    
    /**
     * SelectExpression ::=
     *      IdentificationVariable | StateFieldPathExpression |
     *      (AggregateExpression | "(" Subselect ")" | FunctionDeclaration) [["AS"] AliasResultVariable]
     *
     * @return \Doctrine\ORM\Query\AST\SelectExpression
     */
    public function SelectExpression()
    {
        $expression = null;
        $fieldAliasIdentificationVariable = null;
        $peek = $this->_lexer->glimpse();

        // First we recognize for an IdentificationVariable (DQL class alias)
        if ($peek['value'] != '.' && $peek['value'] != '(' && $this->_lexer->lookahead['type'] === Lexer::T_IDENTIFIER) {
            $expression = $this->IdentificationVariable();
        } else if (($isFunction = $this->_isFunction()) !== false || $this->_isSubselect()) {
            if ($isFunction) {
                if ($this->_isAggregateFunction($this->_lexer->lookahead['type'])) {
                    $expression = $this->AggregateExpression();
                } else {
                    $expression = $this->FunctionDeclaration();
                }
            } else {
                $this->match(Lexer::T_OPEN_PARENTHESIS);
                $expression = $this->Subselect();
                $this->match(Lexer::T_CLOSE_PARENTHESIS);
            }

            if ($this->_lexer->isNextToken(Lexer::T_AS)) {
                $this->match(Lexer::T_AS);
            }

            if ($this->_lexer->isNextToken(Lexer::T_IDENTIFIER)) {
                $token = $this->_lexer->lookahead;
                $fieldAliasIdentificationVariable = $this->AliasResultVariable();
                
                // Include AliasResultVariable in query components.
                $this->_queryComponents[$fieldAliasIdentificationVariable] = array(
                    'resultVariable' => $expression,
                    'nestingLevel'   => $this->_nestingLevel,
                    'token'          => $token,
                );
            }
        } else {
            // Deny hydration of partial objects if doctrine.forcePartialLoad query hint not defined 
            if (
                $this->_query->getHydrationMode() == Query::HYDRATE_OBJECT &&
                ! $this->_query->getHint(Query::HINT_FORCE_PARTIAL_LOAD)
            ) {
            	throw DoctrineException::partialObjectsAreDangerous();
            }

            $expression = $this->StateFieldPathExpression();
        }

        return new AST\SelectExpression($expression, $fieldAliasIdentificationVariable);
    }

    /**
     * SimpleSelectExpression ::= StateFieldPathExpression | IdentificationVariable | (AggregateExpression [["AS"] AliasResultVariable])
     *
     * @return \Doctrine\ORM\Query\AST\SimpleSelectExpression
     */
    public function SimpleSelectExpression()
    {
        if ($this->_lexer->isNextToken(Lexer::T_IDENTIFIER)) {
            // SingleValuedPathExpression | IdentificationVariable
            $peek = $this->_lexer->glimpse();

            if ($peek['value'] == '.') {
                return new AST\SimpleSelectExpression($this->StateFieldPathExpression());
            }

            $this->match(Lexer::T_IDENTIFIER);

            return new AST\SimpleSelectExpression($this->_lexer->token['value']);
        }
        
        $expr = new AST\SimpleSelectExpression($this->AggregateExpression());

        if ($this->_lexer->isNextToken(Lexer::T_AS)) {
            $this->match(Lexer::T_AS);
        }

        if ($this->_lexer->isNextToken(Lexer::T_IDENTIFIER)) {
            $token = $this->_lexer->lookahead;
            $resultVariable = $this->AliasResultVariable();
            $expr->fieldIdentificationVariable = $resultVariable;
                
            // Include AliasResultVariable in query components.
            $this->_queryComponents[$resultVariable] = array(
                'resultvariable' => $expr,
                'nestingLevel'   => $this->_nestingLevel,
                'token'          => $token,
            );
        }

        return $expr;
    }

    
    /**
     * ConditionalExpression ::= ConditionalTerm {"OR" ConditionalTerm}*
     *
     * @return \Doctrine\ORM\Query\AST\ConditionalExpression
     */
    public function ConditionalExpression()
    {
        $conditionalTerms = array();
        $conditionalTerms[] = $this->ConditionalTerm();

        while ($this->_lexer->isNextToken(Lexer::T_OR)) {
            $this->match(Lexer::T_OR);
            $conditionalTerms[] = $this->ConditionalTerm();
        }

        return new AST\ConditionalExpression($conditionalTerms);
    }

    /**
     * ConditionalTerm ::= ConditionalFactor {"AND" ConditionalFactor}*
     *
     * @return \Doctrine\ORM\Query\AST\ConditionalTerm
     */
    public function ConditionalTerm()
    {
        $conditionalFactors = array();
        $conditionalFactors[] = $this->ConditionalFactor();

        while ($this->_lexer->isNextToken(Lexer::T_AND)) {
            $this->match(Lexer::T_AND);
            $conditionalFactors[] = $this->ConditionalFactor();
        }

        return new AST\ConditionalTerm($conditionalFactors);
    }

    /**
     * ConditionalFactor ::= ["NOT"] ConditionalPrimary
     *
     * @return \Doctrine\ORM\Query\AST\ConditionalFactor
     */
    public function ConditionalFactor()
    {
        $not = false;

        if ($this->_lexer->isNextToken(Lexer::T_NOT)) {
            $this->match(Lexer::T_NOT);
            $not = true;
        }

        $condFactor = new AST\ConditionalFactor($this->ConditionalPrimary());
        $condFactor->not = $not;
        
        return $condFactor;
    }

    /**
     * ConditionalPrimary ::= SimpleConditionalExpression | "(" ConditionalExpression ")"
     *
     * @return Doctrine\ORM\Query\AST\ConditionalPrimary
     */
    public function ConditionalPrimary()
    {
        $condPrimary = new AST\ConditionalPrimary;
        
        if ($this->_lexer->isNextToken(Lexer::T_OPEN_PARENTHESIS)) {
            // Peek beyond the matching closing paranthesis ')'
            $numUnmatched = 1;
            $peek = $this->_lexer->peek();
            while ($numUnmatched > 0 && $peek !== null) {
                if ($peek['value'] == ')') {
                    --$numUnmatched;
                } else if ($peek['value'] == '(') {
                    ++$numUnmatched;
                }
                $peek = $this->_lexer->peek();
            }
            $this->_lexer->resetPeek();
       
            if (in_array($peek['value'], array("=",  "<", "<=", "<>", ">", ">=", "!=")) ||
                    $peek['type'] === Lexer::T_NOT ||
                    $peek['type'] === Lexer::T_BETWEEN ||
                    $peek['type'] === Lexer::T_LIKE ||
                    $peek['type'] === Lexer::T_IN ||
                    $peek['type'] === Lexer::T_IS ||
                    $peek['type'] === Lexer::T_EXISTS) {
                $condPrimary->simpleConditionalExpression = $this->SimpleConditionalExpression();
            } else {
                $this->match(Lexer::T_OPEN_PARENTHESIS);
                $condPrimary->conditionalExpression = $this->ConditionalExpression();
                $this->match(Lexer::T_CLOSE_PARENTHESIS);
            }
        } else {
            $condPrimary->simpleConditionalExpression = $this->SimpleConditionalExpression();
        }
        
        return $condPrimary;
    }

    /**
     * SimpleConditionalExpression ::=
     *      ComparisonExpression | BetweenExpression | LikeExpression |
     *      InExpression | NullComparisonExpression | ExistsExpression |
     *      EmptyCollectionComparisonExpression | CollectionMemberExpression
     */
    public function SimpleConditionalExpression()
    {
        if ($this->_lexer->isNextToken(Lexer::T_NOT)) {
            $token = $this->_lexer->glimpse();
        } else {
            $token = $this->_lexer->lookahead;
        }

        if ($token['type'] === Lexer::T_EXISTS) {
            return $this->ExistsExpression();
        }

        $pathExprOrInputParam = false;

        if ($token['type'] === Lexer::T_IDENTIFIER || $token['type'] === Lexer::T_INPUT_PARAMETER) {
            // Peek beyond the PathExpression
            $pathExprOrInputParam = true;
            $peek = $this->_lexer->peek();

            while ($peek['value'] === '.') {
                $this->_lexer->peek();
                $peek = $this->_lexer->peek();
            }

            // Also peek beyond a NOT if there is one
            if ($peek['type'] === Lexer::T_NOT) {
                $peek = $this->_lexer->peek();
            }
            
            $token = $peek;
            
            // We need to go even further in case of IS (differenciate between NULL and EMPTY)
            $lookahead = $this->_lexer->peek();
                
            // Also peek beyond a NOT if there is one
            if ($lookahead['type'] === Lexer::T_NOT) {
                $lookahead = $this->_lexer->peek();
            }
            
            $this->_lexer->resetPeek();
        }

        if ($pathExprOrInputParam) {            
            switch ($token['type']) {
                case Lexer::T_EQUALS:
                case Lexer::T_LOWER_THAN:
                case Lexer::T_GREATER_THAN:
                case Lexer::T_NEGATE:
                case Lexer::T_OPEN_PARENTHESIS:
                    return $this->ComparisonExpression();

                case Lexer::T_BETWEEN:
                    return $this->BetweenExpression();

                case Lexer::T_LIKE:
                    return $this->LikeExpression();

                case Lexer::T_IN:
                    return $this->InExpression();

                case Lexer::T_IS:
                	if ($lookahead['type'] == Lexer::T_NULL) {
                        return $this->NullComparisonExpression();
                    }
                    
                    return $this->EmptyCollectionComparisonExpression();

                case Lexer::T_MEMBER:
                    return $this->CollectionMemberExpression();

                default:
                    $this->syntaxError();
            }
        }
        
        return $this->ComparisonExpression();
    }
    
    
    /**
     * EmptyCollectionComparisonExpression ::= CollectionValuedPathExpression "IS" ["NOT"] "EMPTY"
     *
     * @return \Doctrine\ORM\Query\AST\EmptyCollectionComparisonExpression
     */
    public function EmptyCollectionComparisonExpression()
    {
        $emptyColletionCompExpr = new AST\EmptyCollectionComparisonExpression(
            $this->CollectionValuedPathExpression()
        );
        $this->match(Lexer::T_IS);

        if ($this->_lexer->isNextToken(Lexer::T_NOT)) {
            $this->match(Lexer::T_NOT);
            $emptyColletionCompExpr->not = true;
        }

        $this->match(Lexer::T_EMPTY);

        return $emptyColletionCompExpr;
    }
    
    /**
     * CollectionMemberExpression ::= EntityExpression ["NOT"] "MEMBER" ["OF"] CollectionValuedPathExpression
     * 
     * EntityExpression ::= SingleValuedAssociationPathExpression | SimpleEntityExpression
     * SimpleEntityExpression ::= IdentificationVariable | InputParameter
     * 
     * @return \Doctrine\ORM\Query\AST\CollectionMemberExpression
     */
    public function CollectionMemberExpression()
    {
        $not = false;

        $entityExpr = $this->EntityExpression(); 

        if ($this->_lexer->isNextToken(Lexer::T_NOT)) {
            $not = true;
            $this->match(Lexer::T_NOT);
        }

        $this->match(Lexer::T_MEMBER);

        if ($this->_lexer->isNextToken(Lexer::T_OF)) {
            $this->match(Lexer::T_OF);
        }

        $collMemberExpr = new AST\CollectionMemberExpression(
            $entityExpr, $this->CollectionValuedPathExpression()
        );
        $collMemberExpr->not = $not;
        
        return $collMemberExpr;
    }

    
    /**
     * Literal ::= string | char | integer | float | boolean
     *
     * @return string
     */
    public function Literal()
    {
        switch ($this->_lexer->lookahead['type']) {
            case Lexer::T_STRING:
                $this->match(Lexer::T_STRING);
                return new AST\Literal(AST\Literal::STRING, $this->_lexer->token['value']);
                
            case Lexer::T_INTEGER:
            case Lexer::T_FLOAT:
                $this->match(
                    $this->_lexer->isNextToken(Lexer::T_INTEGER) ? Lexer::T_INTEGER : Lexer::T_FLOAT
                );
                return new AST\Literal(AST\Literal::NUMERIC, $this->_lexer->token['value']);
                
            case Lexer::T_TRUE:
            case Lexer::T_FALSE:
                $this->match(
                    $this->_lexer->isNextToken(Lexer::T_TRUE) ? Lexer::T_TRUE : Lexer::T_FALSE
                );
                return new AST\Literal(AST\Literal::BOOLEAN, $this->_lexer->token['value']);
                
            default:
                $this->syntaxError('Literal');
        }
    }
    
    /**
     * InParameter ::= Literal | InputParameter
     *
     * @return string | \Doctrine\ORM\Query\AST\InputParameter
     */
    public function InParameter()
    {
        if ($this->_lexer->lookahead['type'] == Lexer::T_INPUT_PARAMETER) {
            return $this->InputParameter();
        }
        
        return $this->Literal();
    }
    
    
    /**
     * InputParameter ::= PositionalParameter | NamedParameter
     *
     * @return \Doctrine\ORM\Query\AST\InputParameter
     */
    public function InputParameter()
    {
        $this->match(Lexer::T_INPUT_PARAMETER);

        return new AST\InputParameter($this->_lexer->token['value']);
    }
    
    
    /**
     * ArithmeticExpression ::= SimpleArithmeticExpression | "(" Subselect ")"
     *
     * @return \Doctrine\ORM\Query\AST\ArithmeticExpression
     */
    public function ArithmeticExpression()
    {
        $expr = new AST\ArithmeticExpression;

        if ($this->_lexer->isNextToken(Lexer::T_OPEN_PARENTHESIS)) {
            $peek = $this->_lexer->glimpse();

            if ($peek['type'] === Lexer::T_SELECT) {
                $this->match(Lexer::T_OPEN_PARENTHESIS);
                $expr->subselect = $this->Subselect();
                $this->match(Lexer::T_CLOSE_PARENTHESIS);

                return $expr;
            }
        }

        $expr->simpleArithmeticExpression = $this->SimpleArithmeticExpression();

        return $expr;
    }

    /**
     * SimpleArithmeticExpression ::= ArithmeticTerm {("+" | "-") ArithmeticTerm}*
     *
     * @return \Doctrine\ORM\Query\AST\SimpleArithmeticExpression
     */
    public function SimpleArithmeticExpression()
    {
        $terms = array();
        $terms[] = $this->ArithmeticTerm();

        while (($isPlus = $this->_lexer->isNextToken(Lexer::T_PLUS)) || $this->_lexer->isNextToken(Lexer::T_MINUS)) {
            $this->match(($isPlus) ? Lexer::T_PLUS : Lexer::T_MINUS);

            $terms[] = $this->_lexer->token['value'];
            $terms[] = $this->ArithmeticTerm();
        }
        
        return new AST\SimpleArithmeticExpression($terms);
    }

    /**
     * ArithmeticTerm ::= ArithmeticFactor {("*" | "/") ArithmeticFactor}*
     *
     * @return \Doctrine\ORM\Query\AST\ArithmeticTerm
     */
    public function ArithmeticTerm()
    {
        $factors = array();
        $factors[] = $this->ArithmeticFactor();

        while (($isMult = $this->_lexer->isNextToken(Lexer::T_MULTIPLY)) || $this->_lexer->isNextToken(Lexer::T_DIVIDE)) {
            $this->match(($isMult) ? Lexer::T_MULTIPLY : Lexer::T_DIVIDE);
            
            $factors[] = $this->_lexer->token['value'];
            $factors[] = $this->ArithmeticFactor();
        }

        return new AST\ArithmeticTerm($factors);
    }

    /**
     * ArithmeticFactor ::= [("+" | "-")] ArithmeticPrimary
     *
     * @return \Doctrine\ORM\Query\AST\ArithmeticFactor
     */
    public function ArithmeticFactor()
    {
       $sign = null;

       if (($isPlus = $this->_lexer->isNextToken(Lexer::T_PLUS)) || $this->_lexer->isNextToken(Lexer::T_MINUS)) {
           $this->match(($isPlus) ? Lexer::T_PLUS : Lexer::T_MINUS);
           $sign = $isPlus;
       }
        
        return new AST\ArithmeticFactor($this->ArithmeticPrimary(), $sign);
    }

    /**
     * ArithmeticPrimary ::= SingleValuedPathExpression | Literal | "(" SimpleArithmeticExpression ")"
     *          | FunctionsReturningNumerics | AggregateExpression | FunctionsReturningStrings
     *          | FunctionsReturningDatetime | IdentificationVariable
     */
    public function ArithmeticPrimary()
    {
        if ($this->_lexer->isNextToken(Lexer::T_OPEN_PARENTHESIS)) {
            $this->match(Lexer::T_OPEN_PARENTHESIS);
            $expr = $this->SimpleArithmeticExpression();
            
            $this->match(Lexer::T_CLOSE_PARENTHESIS);

            return $expr;
        }

        switch ($this->_lexer->lookahead['type']) {
            case Lexer::T_IDENTIFIER:
                $peek = $this->_lexer->glimpse();

                if ($peek['value'] == '(') {
                    return $this->FunctionDeclaration();
                }

                if ($peek['value'] == '.') {
                    return $this->SingleValuedPathExpression();
                }

                return $this->IdentificationVariable();

            case Lexer::T_INPUT_PARAMETER:
                return $this->InputParameter();

            default:
                $peek = $this->_lexer->glimpse();

                if ($peek['value'] == '(') {
                    if ($this->_isAggregateFunction($this->_lexer->lookahead['type'])) {
                        return $this->AggregateExpression();
                    }

                    return $this->FunctionDeclaration();
                } else {
                    return $this->Literal();
                }
        }
    }
    
    /**
     * StringExpression ::= StringPrimary | "(" Subselect ")"
     *
     * @return \Doctrine\ORM\Query\AST\StringPrimary |
     *         \Doctrine]ORM\Query\AST\Subselect
     */
    public function StringExpression()
    {
        if ($this->_lexer->isNextToken(Lexer::T_OPEN_PARENTHESIS)) {
            $peek = $this->_lexer->glimpse();

            if ($peek['type'] === Lexer::T_SELECT) {
                $this->match(Lexer::T_OPEN_PARENTHESIS);
                $expr = $this->Subselect();
                $this->match(Lexer::T_CLOSE_PARENTHESIS);

                return $expr;
            }
        }

        return $this->StringPrimary();
    }

    /**
     * StringPrimary ::= StateFieldPathExpression | string | InputParameter | FunctionsReturningStrings | AggregateExpression
     */
    public function StringPrimary()
    {
        if ($this->_lexer->isNextToken(Lexer::T_IDENTIFIER)) {
            $peek = $this->_lexer->glimpse();

            if ($peek['value'] == '.') {
                return $this->StateFieldPathExpression();
            } else if ($peek['value'] == '(') {
                return $this->FunctionsReturningStrings();
            } else {
                $this->syntaxError("'.' or '('");
            }
        } else if ($this->_lexer->isNextToken(Lexer::T_STRING)) {
            $this->match(Lexer::T_STRING);

            return $this->_lexer->token['value'];
        } else if ($this->_lexer->isNextToken(Lexer::T_INPUT_PARAMETER)) {
            return $this->InputParameter();
        } else if ($this->_isAggregateFunction($this->_lexer->lookahead['type'])) {
            return $this->AggregateExpression();
        }

        $this->syntaxError('StateFieldPathExpression | string | InputParameter | FunctionsReturningStrings | AggregateExpression');
    }

    /**
     * EntityExpression ::= SingleValuedAssociationPathExpression | SimpleEntityExpression
     *
     * @return \Doctrine\ORM\Query\AST\SingleValuedAssociationPathExpression |
     *         \Doctrine\ORM\Query\AST\SimpleEntityExpression
     */
    public function EntityExpression()
    {
        $glimpse = $this->_lexer->glimpse();
        
        if ($this->_lexer->isNextToken(Lexer::T_IDENTIFIER) && $glimpse['value'] === '.') {
            return $this->SingleValuedAssociationPathExpression();
        }
        
        return $this->SimpleEntityExpression();
    }
    
    /**
     * SimpleEntityExpression ::= IdentificationVariable | InputParameter
     *
     * @return string | \Doctrine\ORM\Query\AST\InputParameter
     */
    public function SimpleEntityExpression()
    {
        if ($this->_lexer->isNextToken(Lexer::T_INPUT_PARAMETER)) {
            return $this->InputParameter();
        }
        
        return $this->IdentificationVariable();
    }

    
    /**
     * AggregateExpression ::=
     *  ("AVG" | "MAX" | "MIN" | "SUM") "(" ["DISTINCT"] StateFieldPathExpression ")" |
     *  "COUNT" "(" ["DISTINCT"] (IdentificationVariable | SingleValuedPathExpression) ")"
     *
     * @return \Doctrine\ORM\Query\AST\AggregateExpression
     */
    public function AggregateExpression()
    {
        $isDistinct = false;
        $functionName = '';

        if ($this->_lexer->isNextToken(Lexer::T_COUNT)) {
            $this->match(Lexer::T_COUNT);
            $functionName = $this->_lexer->token['value'];
            $this->match(Lexer::T_OPEN_PARENTHESIS);

            if ($this->_lexer->isNextToken(Lexer::T_DISTINCT)) {
                $this->match(Lexer::T_DISTINCT);
                $isDistinct = true;
            }

            $pathExp = $this->SingleValuedPathExpression();
            $this->match(Lexer::T_CLOSE_PARENTHESIS);
        } else {
            if ($this->_lexer->isNextToken(Lexer::T_AVG)) {
                $this->match(Lexer::T_AVG);
            } else if ($this->_lexer->isNextToken(Lexer::T_MAX)) {
                $this->match(Lexer::T_MAX);
            } else if ($this->_lexer->isNextToken(Lexer::T_MIN)) {
                $this->match(Lexer::T_MIN);
            } else if ($this->_lexer->isNextToken(Lexer::T_SUM)) {
                $this->match(Lexer::T_SUM);
            } else {
                $this->syntaxError('One of: MAX, MIN, AVG, SUM, COUNT');
            }

            $functionName = $this->_lexer->token['value'];
            $this->match(Lexer::T_OPEN_PARENTHESIS);
            $pathExp = $this->StateFieldPathExpression();
            $this->match(Lexer::T_CLOSE_PARENTHESIS);
        }

        return new AST\AggregateExpression($functionName, $pathExp, $isDistinct);
    }

    
    /**
     * QuantifiedExpression ::= ("ALL" | "ANY" | "SOME") "(" Subselect ")"
     *
     * @return \Doctrine\ORM\Query\AST\QuantifiedExpression
     */
    public function QuantifiedExpression()
    {
        $type = '';

        if ($this->_lexer->isNextToken(Lexer::T_ALL)) {
            $this->match(Lexer::T_ALL);
            $type = 'ALL';
        } else if ($this->_lexer->isNextToken(Lexer::T_ANY)) {
            $this->match(Lexer::T_ANY);
             $type = 'ANY';
        } else if ($this->_lexer->isNextToken(Lexer::T_SOME)) {
            $this->match(Lexer::T_SOME);
             $type = 'SOME';
        } else {
            $this->syntaxError('ALL, ANY or SOME');
        }

        $this->match(Lexer::T_OPEN_PARENTHESIS);
        $qExpr = new AST\QuantifiedExpression($this->Subselect());
        $qExpr->type = $type;
        $this->match(Lexer::T_CLOSE_PARENTHESIS);

        return $qExpr;
    }

    /**
     * BetweenExpression ::= ArithmeticExpression ["NOT"] "BETWEEN" ArithmeticExpression "AND" ArithmeticExpression
     *
     * @return \Doctrine\ORM\Query\AST\BetweenExpression
     */
    public function BetweenExpression()
    {
        $not = false;
        $arithExpr1 = $this->ArithmeticExpression();

        if ($this->_lexer->isNextToken(Lexer::T_NOT)) {
            $this->match(Lexer::T_NOT);
            $not = true;
        }

        $this->match(Lexer::T_BETWEEN);
        $arithExpr2 = $this->ArithmeticExpression();
        $this->match(Lexer::T_AND);
        $arithExpr3 = $this->ArithmeticExpression();

        $betweenExpr = new AST\BetweenExpression($arithExpr1, $arithExpr2, $arithExpr3);
        $betweenExpr->not = $not;

        return $betweenExpr;
    }

    /**
     * ComparisonExpression ::= ArithmeticExpression ComparisonOperator ( QuantifiedExpression | ArithmeticExpression )
     *
     * @return \Doctrine\ORM\Query\AST\ComparisonExpression
     */
    public function ComparisonExpression()
    {
        $peek = $this->_lexer->glimpse();

        $leftExpr = $this->ArithmeticExpression();
        $operator = $this->ComparisonOperator();

        if ($this->_isNextAllAnySome()) {
            $rightExpr = $this->QuantifiedExpression();
        } else {
            $rightExpr = $this->ArithmeticExpression();
        }

        return new AST\ComparisonExpression($leftExpr, $operator, $rightExpr);
    }

    /**
     * InExpression ::= StateFieldPathExpression ["NOT"] "IN" "(" (InParameter {"," InParameter}* | Subselect) ")"
     *
     * @return \Doctrine\ORM\Query\AST\InExpression
     */
    public function InExpression()
    {
        $inExpression = new AST\InExpression($this->StateFieldPathExpression());

        if ($this->_lexer->isNextToken(Lexer::T_NOT)) {
            $this->match(Lexer::T_NOT);
            $inExpression->not = true;
        }

        $this->match(Lexer::T_IN);
        $this->match(Lexer::T_OPEN_PARENTHESIS);

        if ($this->_lexer->isNextToken(Lexer::T_SELECT)) {
            $inExpression->subselect = $this->Subselect();
        } else {
            $literals = array();
            $literals[] = $this->InParameter();

            while ($this->_lexer->isNextToken(Lexer::T_COMMA)) {
                $this->match(Lexer::T_COMMA);
                $literals[] = $this->InParameter();
            }

            $inExpression->literals = $literals;
        }

        $this->match(Lexer::T_CLOSE_PARENTHESIS);

        return $inExpression;
    }

    /**
     * LikeExpression ::= StringExpression ["NOT"] "LIKE" (string | input_parameter) ["ESCAPE" char]
     *
     * @return \Doctrine\ORM\Query\AST\LikeExpression
     */
    public function LikeExpression()
    {
        $stringExpr = $this->StringExpression();
        $not = false;

        if ($this->_lexer->isNextToken(Lexer::T_NOT)) {
            $this->match(Lexer::T_NOT);
            $not = true;
        }

        $this->match(Lexer::T_LIKE);

        if ($this->_lexer->isNextToken(Lexer::T_INPUT_PARAMETER)) {
            $this->match(Lexer::T_INPUT_PARAMETER);
            $stringPattern = new AST\InputParameter($this->_lexer->token['value']);
        } else {
            $this->match(Lexer::T_STRING);
            $stringPattern = $this->_lexer->token['value'];
        }

        $escapeChar = null;

        if ($this->_lexer->lookahead['type'] === Lexer::T_ESCAPE) {
            $this->match(Lexer::T_ESCAPE);
            $this->match(Lexer::T_STRING);
            $escapeChar = $this->_lexer->token['value'];
        }

        $likeExpr = new AST\LikeExpression($stringExpr, $stringPattern, $escapeChar);
        $likeExpr->not = $not;
        
        return $likeExpr;
    }

    /**
     * NullComparisonExpression ::= (SingleValuedPathExpression | InputParameter) "IS" ["NOT"] "NULL"
     *
     * @return \Doctrine\ORM\Query\AST\NullComparisonExpression
     */
    public function NullComparisonExpression()
    {
        if ($this->_lexer->isNextToken(Lexer::T_INPUT_PARAMETER)) {
            $this->match(Lexer::T_INPUT_PARAMETER);
            $expr = new AST\InputParameter($this->_lexer->token['value']);
        } else {
            $expr = $this->SingleValuedPathExpression();
        }

        $nullCompExpr = new AST\NullComparisonExpression($expr);
        $this->match(Lexer::T_IS);

        if ($this->_lexer->isNextToken(Lexer::T_NOT)) {
            $this->match(Lexer::T_NOT);
            $nullCompExpr->not = true;
        }

        $this->match(Lexer::T_NULL);

        return $nullCompExpr;
    }

    /**
     * ExistsExpression ::= ["NOT"] "EXISTS" "(" Subselect ")"
     *
     * @return \Doctrine\ORM\Query\AST\ExistsExpression
     */
    public function ExistsExpression()
    {
        $not = false;

        if ($this->_lexer->isNextToken(Lexer::T_NOT)) {
            $this->match(Lexer::T_NOT);
            $not = true;
        }

        $this->match(Lexer::T_EXISTS);
        $this->match(Lexer::T_OPEN_PARENTHESIS);
        $existsExpression = new AST\ExistsExpression($this->Subselect());
        $existsExpression->not = $not;
        $this->match(Lexer::T_CLOSE_PARENTHESIS);

        return $existsExpression;
    }

    /**
     * ComparisonOperator ::= "=" | "<" | "<=" | "<>" | ">" | ">=" | "!="
     *
     * @return string
     */
    public function ComparisonOperator()
    {
        switch ($this->_lexer->lookahead['value']) {
            case '=':
                $this->match(Lexer::T_EQUALS);

                return '=';

            case '<':
                $this->match(Lexer::T_LOWER_THAN);
                $operator = '<';

                if ($this->_lexer->isNextToken(Lexer::T_EQUALS)) {
                    $this->match(Lexer::T_EQUALS);
                    $operator .= '=';
                } else if ($this->_lexer->isNextToken(Lexer::T_GREATER_THAN)) {
                    $this->match(Lexer::T_GREATER_THAN);
                    $operator .= '>';
                }

                return $operator;

            case '>':
                $this->match(Lexer::T_GREATER_THAN);
                $operator = '>';

                if ($this->_lexer->isNextToken(Lexer::T_EQUALS)) {
                    $this->match(Lexer::T_EQUALS);
                    $operator .= '=';
                }

                return $operator;

            case '!':
                $this->match(Lexer::T_NEGATE);
                $this->match(Lexer::T_EQUALS);

                return '<>';

            default:
                $this->syntaxError('=, <, <=, <>, >, >=, !=');
        }
    }

    
    /**
     * FunctionDeclaration ::= FunctionsReturningStrings | FunctionsReturningNumerics | FunctionsReturningDatetime
     */
    public function FunctionDeclaration()
    {
        $funcName = $this->_lexer->lookahead['value'];

        if ($this->_isStringFunction($funcName)) {
            return $this->FunctionsReturningStrings();
        } else if ($this->_isNumericFunction($funcName)) {
            return $this->FunctionsReturningNumerics();
        } else if ($this->_isDatetimeFunction($funcName)) {
            return $this->FunctionsReturningDatetime();
        }
        
        $this->syntaxError('Known function.');
    }

    /**
     * FunctionsReturningNumerics ::=
     *      "LENGTH" "(" StringPrimary ")" |
     *      "LOCATE" "(" StringPrimary "," StringPrimary ["," SimpleArithmeticExpression]")" |
     *      "ABS" "(" SimpleArithmeticExpression ")" |
     *      "SQRT" "(" SimpleArithmeticExpression ")" |
     *      "MOD" "(" SimpleArithmeticExpression "," SimpleArithmeticExpression ")" |
     *      "SIZE" "(" CollectionValuedPathExpression ")"
     */
    public function FunctionsReturningNumerics()
    {
        $funcNameLower = strtolower($this->_lexer->lookahead['value']);
        $funcClass = self::$_NUMERIC_FUNCTIONS[$funcNameLower];
        $function = new $funcClass($funcNameLower);
        $function->parse($this);

        return $function;
    }

    /**
     * FunctionsReturningDateTime ::= "CURRENT_DATE" | "CURRENT_TIME" | "CURRENT_TIMESTAMP"
     */
    public function FunctionsReturningDatetime()
    {
        $funcNameLower = strtolower($this->_lexer->lookahead['value']);
        $funcClass = self::$_DATETIME_FUNCTIONS[$funcNameLower];
        $function = new $funcClass($funcNameLower);
        $function->parse($this);

        return $function;
    }
    
    /**
     * FunctionsReturningStrings ::=
     *   "CONCAT" "(" StringPrimary "," StringPrimary ")" |
     *   "SUBSTRING" "(" StringPrimary "," SimpleArithmeticExpression "," SimpleArithmeticExpression ")" |
     *   "TRIM" "(" [["LEADING" | "TRAILING" | "BOTH"] [char] "FROM"] StringPrimary ")" |
     *   "LOWER" "(" StringPrimary ")" |
     *   "UPPER" "(" StringPrimary ")"
     */
    public function FunctionsReturningStrings()
    {
        $funcNameLower = strtolower($this->_lexer->lookahead['value']);
        $funcClass = self::$_STRING_FUNCTIONS[$funcNameLower];
        $function = new $funcClass($funcNameLower);
        $function->parse($this);

        return $function;
    }
}
