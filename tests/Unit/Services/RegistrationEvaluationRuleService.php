<?php

namespace Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use App\Services\RegistrationEvaluationRuleService;
use Mockery; 

/**
 * Fake leve que simula o modelo LegacyRegistration,
 * evitando qualquer conexão com o banco de dados.
 */


class FakeLegacyRegistration extends \App\Models\LegacyRegistration
{
    public function __construct(array $props = [])
    {
        foreach ($props as $key => $value) {
            $this->$key = $value;
        }
    }

    // Evita chamadas ao banco
    public function getConnection()
    {
        return new class {
            public function getName() { return 'fake_connection'; }
        };
    }
}

class RegistrationEvaluationRuleServiceTest extends TestCase
{

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
    
    private function makeRegistration(array $props)
    {
        return new FakeLegacyRegistration($props);
    }

    public function test_ct1_escola_usa_regra_diferenciada_e_ha_regra_configurada()
    {
        $service = new RegistrationEvaluationRuleService();

        $registration = $this->makeRegistration([
            'school' => (object)['utiliza_regra_diferenciada' => true],
            'grade' => new class {
                public function evaluationRules()
                {
                    return new class {
                        public function wherePivot() { return $this; }
                        public function first() {
                            return (object)[
                                'id' => 99,
                                'pivot' => (object)['regra_avaliacao_diferenciada_id' => 99],
                                'regra_diferenciada_id' => null
                            ];
                        }
                    };
                }
            },
            'ano' => 2025,
            'student' => (object)[
                'person' => new class {
                    public function considerableDeficiencies()
                    {
                        return new class {
                            public function exists() { return false; }
                        };
                    }
                }
            ]
        ]);

        $mockRule = (object)['id' => 99];
        Mockery::mock('alias:App\Models\LegacyEvaluationRule')
            ->shouldReceive('find')
            ->with(99)
            ->andReturn($mockRule);

        $result = $service->getEvaluationRule($registration);
        $this->assertEquals(99, $result->id);
    }

    public function test_ct2_escola_usa_regra_diferenciada_mas_sem_regra()
    {
        $service = new RegistrationEvaluationRuleService();

        $registration = $this->makeRegistration([
            'school' => (object)['utiliza_regra_diferenciada' => true],
            'grade' => new class {
                public function evaluationRules()
                {
                    return new class {
                        public function wherePivot() { return $this; }
                        public function first() {
                            return (object)[
                                'id' => null,
                                'pivot' => (object)[],
                                'regra_diferenciada_id' => null
                            ];
                        }
                    };
                }
            },
            'ano' => 2025,
            'student' => (object)[
                'person' => new class {
                    public function considerableDeficiencies()
                    {
                        return new class {
                            public function exists() { return false; }
                        };
                    }
                }
            ]
        ]);

        $result = $service->getEvaluationRule($registration);
        $this->assertNotNull($result);
    }

    public function test_ct3_escola_nao_usa_regra_diferenciada_mas_ha_regra_no_pivot()
    {
        $service = new RegistrationEvaluationRuleService();

        $registration = $this->makeRegistration([
            'school' => (object)['utiliza_regra_diferenciada' => false],
            'grade' => new class {
                public function evaluationRules()
                {
                    return new class {
                        public function wherePivot() { return $this; }
                        public function first() {
                            return (object)[
                                'id' => "111",
                                'pivot' => (object)['regra_avaliacao_diferenciada_id' => 77],
                                'regra_diferenciada_id' => null
                            ];
                        }
                    };
                }
            },
            'ano' => 2025,
            'student' => (object)[
                'person' => new class {
                    public function considerableDeficiencies()
                    {
                        return new class {
                            public function exists() { return false; }
                        };
                    }
                }
            ]
        ]);

        $result = $service->getEvaluationRule($registration);
        $this->assertNotEquals(77, $result->id ?? null);
    }

    public function test_ct4_aluno_com_deficiencia_e_ha_regra_diferenciada_para_deficiencia()
    {
        $service = new RegistrationEvaluationRuleService();

        $registration = $this->makeRegistration([
            'school' => (object)['utiliza_regra_diferenciada' => false],
            'grade' => new class {
                public function evaluationRules()
                {
                    return new class {
                        public function wherePivot() { return $this; }
                        public function first() {
                            return (object)[
                                'id' => 55, 
                                'pivot' => (object)[],
                                'regra_diferenciada_id' => 55  
                            ];
                        }
                    };
                }
            },
            'ano' => 2025,
            'student' => (object)[
                'person' => new class {
                    public function considerableDeficiencies()
                    {
                        return new class {
                            public function exists() { return true; }
                        };
                    }
                }
            ]
        ])

        $mockRule = (object)['id' => 55];
        Mockery::mock('alias:App\Models\LegacyEvaluationRule')
            ->shouldReceive('find')
            ->with(55) 
            ->andReturn($mockRule); 

        $result = $service->getEvaluationRule($registration);
        $this->assertEquals(55, $result->id);
    }

    public function test_ct5_aluno_com_deficiencia_sem_regra_diferenciada()
    {
        $service = new RegistrationEvaluationRuleService();

        $registration = $this->makeRegistration([
            'school' => (object)['utiliza_regra_diferenciada' => false],
            'grade' => new class {
                public function evaluationRules()
                {
                    return new class {
                        public function wherePivot() { return $this; }
                        public function first() {
                            return (object)[
                                'id' => null,
                                'pivot' => (object)[],
                                'regra_diferenciada_id' => null
                            ];
                        }
                    };
                }
            },
            'ano' => 2025,
            'student' => (object)[
                'person' => new class {
                    public function considerableDeficiencies()
                    {
                        return new class {
                            public function exists() { return true; }
                        };
                    }
                }
            ]
        ]);

        $result = $service->getEvaluationRule($registration);
        $this->assertNotNull($result);
    }

    public function test_ct6_aluno_sem_deficiencia_com_regra_diferenciada_configurada()
    {
        $service = new RegistrationEvaluationRuleService();

        $registration = $this->makeRegistration([
            'school' => (object)['utiliza_regra_diferenciada' => false],
            'grade' => new class {
                public function evaluationRules()
                {
                    return new class {
                        public function wherePivot() { return $this; }
                        public function first() {
                            return (object)[
                                'id' => 90,
                                'pivot' => (object)[],
                                'regra_diferenciada_id' => 88
                            ];
                        }
                    };
                }
            },
            'ano' => 2025,
            'student' => (object)[
                'person' => new class {
                    public function considerableDeficiencies()
                    {
                        return new class {
                            public function exists() { return false; }
                        };
                    }
                }
            ]
        ]);

        $result = $service->getEvaluationRule($registration);
        $this->assertNotEquals(88, $result->id ?? null);
    }
}