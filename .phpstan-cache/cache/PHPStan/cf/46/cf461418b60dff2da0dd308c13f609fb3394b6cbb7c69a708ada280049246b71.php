<?php declare(strict_types = 1);

// odsl-/Users/carmelo/Projects/CoquiBot/Toolkits/coqui-awesome-wp/src/AwesomeWpToolkit.php-PHPStan\BetterReflection\Reflection\ReflectionClass-CarmeloSantana\CoquiAwesomeWp\AwesomeWpToolkit
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.65.0.9-8.4.18-2a27a06e61f1c8b3d064b4f7fe2badbf385adae5a12e41961b619b198b996d00',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'CarmeloSantana\\CoquiAwesomeWp\\AwesomeWpToolkit',
        'filename' => '/Users/carmelo/Projects/CoquiBot/Toolkits/coqui-awesome-wp/src/AwesomeWpToolkit.php',
      ),
    ),
    'namespace' => 'CarmeloSantana\\CoquiAwesomeWp',
    'name' => 'CarmeloSantana\\CoquiAwesomeWp\\AwesomeWpToolkit',
    'shortName' => 'AwesomeWpToolkit',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => NULL,
    'attributes' => 
    array (
    ),
    'startLine' => 27,
    'endLine' => 120,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => NULL,
    'implementsClassNames' => 
    array (
      0 => 'CarmeloSantana\\PHPAgents\\Contract\\ToolkitInterface',
    ),
    'traitClassNames' => 
    array (
    ),
    'immediateConstants' => 
    array (
    ),
    'immediateProperties' => 
    array (
      'siteManager' => 
      array (
        'declaringClassName' => 'CarmeloSantana\\CoquiAwesomeWp\\AwesomeWpToolkit',
        'implementingClassName' => 'CarmeloSantana\\CoquiAwesomeWp\\AwesomeWpToolkit',
        'name' => 'siteManager',
        'modifiers' => 4,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'CarmeloSantana\\CoquiAwesomeWp\\Storage\\SiteManager',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 29,
        'endLine' => 29,
        'startColumn' => 5,
        'endColumn' => 37,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'storagePath' => 
      array (
        'declaringClassName' => 'CarmeloSantana\\CoquiAwesomeWp\\AwesomeWpToolkit',
        'implementingClassName' => 'CarmeloSantana\\CoquiAwesomeWp\\AwesomeWpToolkit',
        'name' => 'storagePath',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'string',
            'isIdentifier' => true,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 32,
        'endLine' => 32,
        'startColumn' => 9,
        'endColumn' => 44,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'scaffoldOutputPath' => 
      array (
        'declaringClassName' => 'CarmeloSantana\\CoquiAwesomeWp\\AwesomeWpToolkit',
        'implementingClassName' => 'CarmeloSantana\\CoquiAwesomeWp\\AwesomeWpToolkit',
        'name' => 'scaffoldOutputPath',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'string',
            'isIdentifier' => true,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 33,
        'endLine' => 33,
        'startColumn' => 9,
        'endColumn' => 51,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'httpClient' => 
      array (
        'declaringClassName' => 'CarmeloSantana\\CoquiAwesomeWp\\AwesomeWpToolkit',
        'implementingClassName' => 'CarmeloSantana\\CoquiAwesomeWp\\AwesomeWpToolkit',
        'name' => 'httpClient',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionUnionType',
          'data' => 
          array (
            'types' => 
            array (
              0 => 
              array (
                'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
                'data' => 
                array (
                  'name' => 'Symfony\\Contracts\\HttpClient\\HttpClientInterface',
                  'isIdentifier' => false,
                ),
              ),
              1 => 
              array (
                'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
                'data' => 
                array (
                  'name' => 'null',
                  'isIdentifier' => true,
                ),
              ),
            ),
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 34,
        'endLine' => 34,
        'startColumn' => 9,
        'endColumn' => 64,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
    ),
    'immediateMethods' => 
    array (
      '__construct' => 
      array (
        'name' => '__construct',
        'parameters' => 
        array (
          'storagePath' => 
          array (
            'name' => 'storagePath',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'string',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 32,
            'endLine' => 32,
            'startColumn' => 9,
            'endColumn' => 44,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'scaffoldOutputPath' => 
          array (
            'name' => 'scaffoldOutputPath',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'string',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 33,
            'endLine' => 33,
            'startColumn' => 9,
            'endColumn' => 51,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'httpClient' => 
          array (
            'name' => 'httpClient',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 34,
                'endLine' => 34,
                'startTokenPos' => 165,
                'startFilePos' => 1359,
                'endTokenPos' => 165,
                'endFilePos' => 1362,
              ),
            ),
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionUnionType',
              'data' => 
              array (
                'types' => 
                array (
                  0 => 
                  array (
                    'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
                    'data' => 
                    array (
                      'name' => 'Symfony\\Contracts\\HttpClient\\HttpClientInterface',
                      'isIdentifier' => false,
                    ),
                  ),
                  1 => 
                  array (
                    'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
                    'data' => 
                    array (
                      'name' => 'null',
                      'isIdentifier' => true,
                    ),
                  ),
                ),
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 34,
            'endLine' => 34,
            'startColumn' => 9,
            'endColumn' => 64,
            'parameterIndex' => 2,
            'isOptional' => true,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 31,
        'endLine' => 37,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'CarmeloSantana\\CoquiAwesomeWp',
        'declaringClassName' => 'CarmeloSantana\\CoquiAwesomeWp\\AwesomeWpToolkit',
        'implementingClassName' => 'CarmeloSantana\\CoquiAwesomeWp\\AwesomeWpToolkit',
        'currentClassName' => 'CarmeloSantana\\CoquiAwesomeWp\\AwesomeWpToolkit',
        'aliasName' => NULL,
      ),
      'fromEnv' => 
      array (
        'name' => 'fromEnv',
        'parameters' => 
        array (
          'workspacePath' => 
          array (
            'name' => 'workspacePath',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'string',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 39,
            'endLine' => 39,
            'startColumn' => 36,
            'endColumn' => 56,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'self',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 39,
        'endLine' => 48,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'CarmeloSantana\\CoquiAwesomeWp',
        'declaringClassName' => 'CarmeloSantana\\CoquiAwesomeWp\\AwesomeWpToolkit',
        'implementingClassName' => 'CarmeloSantana\\CoquiAwesomeWp\\AwesomeWpToolkit',
        'currentClassName' => 'CarmeloSantana\\CoquiAwesomeWp\\AwesomeWpToolkit',
        'aliasName' => NULL,
      ),
      'tools' => 
      array (
        'name' => 'tools',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @return ToolInterface[]
 */',
        'startLine' => 53,
        'endLine' => 72,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'CarmeloSantana\\CoquiAwesomeWp',
        'declaringClassName' => 'CarmeloSantana\\CoquiAwesomeWp\\AwesomeWpToolkit',
        'implementingClassName' => 'CarmeloSantana\\CoquiAwesomeWp\\AwesomeWpToolkit',
        'currentClassName' => 'CarmeloSantana\\CoquiAwesomeWp\\AwesomeWpToolkit',
        'aliasName' => NULL,
      ),
      'guidelines' => 
      array (
        'name' => 'guidelines',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'string',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 74,
        'endLine' => 119,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'CarmeloSantana\\CoquiAwesomeWp',
        'declaringClassName' => 'CarmeloSantana\\CoquiAwesomeWp\\AwesomeWpToolkit',
        'implementingClassName' => 'CarmeloSantana\\CoquiAwesomeWp\\AwesomeWpToolkit',
        'currentClassName' => 'CarmeloSantana\\CoquiAwesomeWp\\AwesomeWpToolkit',
        'aliasName' => NULL,
      ),
    ),
    'traitsData' => 
    array (
      'aliases' => 
      array (
      ),
      'modifiers' => 
      array (
      ),
      'precedences' => 
      array (
      ),
      'hashes' => 
      array (
      ),
    ),
  ),
));