PS C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2> php artisan test --coverage                        
   
   FAIL  Tests\Browser\RolePermissionsTest
  ⨯ it shows permission labels without group prefix on the create role page                                       132.92s  

   FAIL  Tests\Browser\WelcomeTest
  ⨯ it has welcome page                                                                                             6.94s  
 


   FAIL  Tests\Unit\ArchTest
  ✓ preset → php                                                                                                  265.19s  
  ⨯ preset → strict                                                                                               275.54s  
  ✓ preset → security → ignoring ['assert']                                                                        16.40s  
  ✓ controllers                                                                                                    74.49s  



   FAIL  Tests\Unit\Models\UserTest
  ⨯ to array                                                                                                        1.91s  


   FAIL  Tests\Feature\Controllers\LabRequestItemConsumableControllerTest
  ⨯ it shows the laboratory worklist to authorized users                                                          112.24s  
  ✓ it shows the laboratory dashboard to authorized users                                                          99.61s  
  ✓ it records and removes consumable usage while syncing actual cost                                              85.40s  

   FAIL  Tests\Feature\Controllers\LabResultWorkflowControllerTest
  ⨯ it picks a sample for a laboratory request item from the incoming queue                                       111.65s  
  ⨯ it stores reviews and approves parameter-panel lab results                                                    102.87s  
  ⨯ it moves a request item between the incoming and enter-results queues after sample picking                    107.30s  
  

   FAIL  Tests\Feature\Controllers\PermissionEnforcementTest
  ✓ Consultation workflow permissions → it allows facility service deletion when no service orders exist           86.27s  
  ⨯ Appointment action permissions → it forbids and allows appointment confirmation based on appointments.confirm… 78.22s  




#40 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\laravel\framework\src\Illuminate\Cookie\Middleware\EncryptCookies.php(74): Illuminate\Pipeline\Pipeline->{closure:{closure:Illuminate\Pipeline\Pipeline::carry():194}:195}(Object(Illuminate\Http\Request))
#41 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\laravel\framework\src\Illuminate\Pipeline\Pipeline.php(219): Illuminate\Cookie\Middleware\EncryptCookies->handle(Object(Illuminate\Http\Request), Object(Closure))     
#42 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\laravel\framework\src\Illuminate\Pipeline\Pipeline.php(137): Illuminate\Pipeline\Pipeline->{closure:{closure:Illuminate\Pipeline\Pipeline::carry():194}:195}(Object(Illuminate\Http\Request))
#43 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\laravel\framework\src\Illuminate\Routing\Router.php(821): Illuminate\Pipeline\Pipeline->then(Object(Closure))
#44 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\laravel\framework\src\Illuminate\Routing\Router.php(800): Illuminate\Routing\Router->runRouteWithinStack(Object(Illuminate\Routing\Route), Object(Illuminate\Http\Request))
#45 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\laravel\framework\src\Illuminate\Routing\Router.php(764): Illuminate\Routing\Router->runRoute(Object(Illuminate\Http\Request), Object(Illuminate\Routing\Route))       
#46 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\laravel\framework\src\Illuminate\Routing\Router.php(753): Illuminate\Routing\Router->dispatchToRoute(Object(Illuminate\Http\Request))
#47 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\laravel\framework\src\Illuminate\Foundation\Http\Kernel.php(200): Illuminate\Routing\Router->dispatch(Object(Illuminate\Http\Request))
#48 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\laravel\framework\src\Illuminate\Pipeline\Pipeline.php(180): Illuminate\Foundation\Http\Kernel->{closure:Illuminate\Foundation\Http\Kernel::dispatchToRouter():197}(Object(Illuminate\Http\Request))
#49 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\laravel\framework\src\Illuminate\Foundation\Http\Middleware\TransformsRequest.php(21): Illuminate\Pipeline\Pipeline->{closure:Illuminate\Pipeline\Pipeline::prepareDestination():178}(Object(Illuminate\Http\Request))
#50 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\laravel\framework\src\Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull.php(31): Illuminate\Foundation\Http\Middleware\TransformsRequest->handle(Object(Illuminate\Http\Request), Object(Closure))
#51 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\laravel\framework\src\Illuminate\Pipeline\Pipeline.php(219): Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull->handle(Object(Illuminate\Http\Request), Object(Closure))
#52 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\laravel\framework\src\Illuminate\Foundation\Http\Middleware\TransformsRequest.php(21): Illuminate\Pipeline\Pipeline->{closure:{closure:Illuminate\Pipeline\Pipeline::carry():194}:195}(Object(Illuminate\Http\Request))
#53 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\laravel\framework\src\Illuminate\Foundation\Http\Middleware\TrimStrings.php(51): Illuminate\Foundation\Http\Middleware\TransformsRequest->handle(Object(Illuminate\Http\Request), Object(Closure))
#54 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\laravel\framework\src\Illuminate\Pipeline\Pipeline.php(219): Illuminate\Foundation\Http\Middleware\TrimStrings->handle(Object(Illuminate\Http\Request), Object(Closure))
#55 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\laravel\framework\src\Illuminate\Http\Middleware\ValidatePostSize.php(27): Illuminate\Pipeline\Pipeline->{closure:{closure:Illuminate\Pipeline\Pipeline::carry():194}:195}(Object(Illuminate\Http\Request))
#56 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\laravel\framework\src\Illuminate\Pipeline\Pipeline.php(219): Illuminate\Http\Middleware\ValidatePostSize->handle(Object(Illuminate\Http\Request), Object(Closure))     
#57 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\laravel\framework\src\Illuminate\Foundation\Http\Middleware\PreventRequestsDuringMaintenance.php(109): Illuminate\Pipeline\Pipeline->{closure:{closure:Illuminate\Pipeline\Pipeline::carry():194}:195}(Object(Illuminate\Http\Request))
#58 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\laravel\framework\src\Illuminate\Pipeline\Pipeline.php(219): Illuminate\Foundation\Http\Middleware\PreventRequestsDuringMaintenance->handle(Object(Illuminate\Http\Request), Object(Closure))
#59 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\laravel\framework\src\Illuminate\Http\Middleware\HandleCors.php(61): Illuminate\Pipeline\Pipeline->{closure:{closure:Illuminate\Pipeline\Pipeline::carry():194}:195}(Object(Illuminate\Http\Request))
#60 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\laravel\framework\src\Illuminate\Pipeline\Pipeline.php(219): Illuminate\Http\Middleware\HandleCors->handle(Object(Illuminate\Http\Request), Object(Closure))
#61 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\laravel\framework\src\Illuminate\Http\Middleware\TrustProxies.php(58): Illuminate\Pipeline\Pipeline->{closure:{closure:Illuminate\Pipeline\Pipeline::carry():194}:195}(Object(Illuminate\Http\Request))
#62 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\laravel\framework\src\Illuminate\Pipeline\Pipeline.php(219): Illuminate\Http\Middleware\TrustProxies->handle(Object(Illuminate\Http\Request), Object(Closure))
#63 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\laravel\framework\src\Illuminate\Foundation\Http\Middleware\InvokeDeferredCallbacks.php(22): Illuminate\Pipeline\Pipeline->{closure:{closure:Illuminate\Pipeline\Pipeline::carry():194}:195}(Object(Illuminate\Http\Request))
#64 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\laravel\framework\src\Illuminate\Pipeline\Pipeline.php(219): Illuminate\Foundation\Http\Middleware\InvokeDeferredCallbacks->handle(Object(Illuminate\Http\Request), Object(Closure))
#65 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\laravel\framework\src\Illuminate\Http\Middleware\ValidatePathEncoding.php(26): Illuminate\Pipeline\Pipeline->{closure:{closure:Illuminate\Pipeline\Pipeline::carry():194}:195}(Object(Illuminate\Http\Request))
#66 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\laravel\framework\src\Illuminate\Pipeline\Pipeline.php(219): Illuminate\Http\Middleware\ValidatePathEncoding->handle(Object(Illuminate\Http\Request), Object(Closure)) 
#67 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\laravel\framework\src\Illuminate\Pipeline\Pipeline.php(137): Illuminate\Pipeline\Pipeline->{closure:{closure:Illuminate\Pipeline\Pipeline::carry():194}:195}(Object(Illuminate\Http\Request))
#68 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\laravel\framework\src\Illuminate\Foundation\Http\Kernel.php(175): Illuminate\Pipeline\Pipeline->then(Object(Closure))
#69 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\laravel\framework\src\Illuminate\Foundation\Http\Kernel.php(144): Illuminate\Foundation\Http\Kernel->sendRequestThroughRouter(Object(Illuminate\Http\Request))
#70 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\laravel\framework\src\Illuminate\Foundation\Testing\Concerns\MakesHttpRequests.php(607): Illuminate\Foundation\Http\Kernel->handle(Object(Illuminate\Http\Request))    
#71 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\laravel\framework\src\Illuminate\Foundation\Testing\Concerns\MakesHttpRequests.php(397): Illuminate\Foundation\Testing\TestCase->call('POST', 'http://localhos...', Array, Array, Array, Array)
#72 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\tests\Feature\Controllers\LabResultWorkflowControllerTest.php(187): Illuminate\Foundation\Testing\TestCase->post('http://localhos...', Array)
#73 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\pestphp\pest\src\Factories\TestCaseMethodFactory.php(172): P\Tests\Feature\Controllers\LabResultWorkflowControllerTest->{closure:C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\tests\Feature\Controllers\LabResultWorkflowControllerTest.php:179}()
#74 [internal function]: P\Tests\Feature\Controllers\LabResultWorkflowControllerTest->{closure:Pest\Factories\TestCaseMethodFactory::getClosure():162}()
#75 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\pestphp\pest\src\Concerns\Testable.php(429): call_user_func_array(Object(Closure), Array)
#76 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\pestphp\pest\src\Support\ExceptionTrace.php(26): P\Tests\Feature\Controllers\LabResultWorkflowControllerTest->{closure:Pest\Concerns\Testable::__callClosure():429}()  
#77 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\pestphp\pest\src\Concerns\Testable.php(429): Pest\Support\ExceptionTrace::ensure(Object(Closure))
#78 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\pestphp\pest\src\Concerns\Testable.php(331): P\Tests\Feature\Controllers\LabResultWorkflowControllerTest->__callClosure(Object(Closure), Array)
#79 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\pestphp\pest\src\Factories\TestCaseFactory.php(170) : eval()'d code(17): P\Tests\Feature\Controllers\LabResultWorkflowControllerTest->__runTest(Object(Closure))       
#80 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\phpunit\phpunit\src\Framework\TestCase.php(1332): P\Tests\Feature\Controllers\LabResultWorkflowControllerTest->__pest_evaluable_it_picks_a_sample_for_a_laboratory_request_item_from_the_incoming_queue()
#81 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\phpunit\phpunit\src\Framework\TestCase.php(519): PHPUnit\Framework\TestCase->runTest()
#82 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\phpunit\phpunit\src\Framework\TestRunner\TestRunner.php(99): PHPUnit\Framework\TestCase->runBare()
#83 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\phpunit\phpunit\src\Framework\TestCase.php(359): PHPUnit\Framework\TestRunner->run(Object(P\Tests\Feature\Controllers\LabResultWorkflowControllerTest))
#84 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\phpunit\phpunit\src\Framework\TestSuite.php(374): PHPUnit\Framework\TestCase->run()
#85 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\phpunit\phpunit\src\Framework\TestSuite.php(374): PHPUnit\Framework\TestSuite->run()
#86 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\phpunit\phpunit\src\Framework\TestSuite.php(374): PHPUnit\Framework\TestSuite->run()
#87 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\phpunit\phpunit\src\TextUI\TestRunner.php(64): PHPUnit\Framework\TestSuite->run()
#88 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\phpunit\phpunit\src\TextUI\Application.php(229): PHPUnit\TextUI\TestRunner->run(Object(PHPUnit\TextUI\Configuration\Configuration), Object(PHPUnit\Runner\ResultCache\DefaultResultCache), Object(PHPUnit\Framework\TestSuite))
#89 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\pestphp\pest\src\Kernel.php(103): PHPUnit\TextUI\Application->run(Array)
#90 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\pestphp\pest\bin\pest(184): Pest\Kernel->handle(Array, Array)
#91 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\pestphp\pest\bin\pest(192): {closure:C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\pestphp\pest\bin\pest:18}()
#92 {main}

----------------------------------------------------------------------------------

The attribute [name] either does not exist or was not retrieved for model [App\Models\SpecimenType].

  at tests\Feature\Controllers\LabResultWorkflowControllerTest.php:192
    188▕             'specimen_type_id' => $specimenType->id,
    189▕             'outside_sample_origin' => 'Referral clinic',
    190▕         ]);
    191▕
  ➜ 192▕     $response->assertRedirectToRoute('laboratory.request-items.show', $requestItem);
    193▕     $response->assertSessionHas('success', 'Sample picked successfully.');
    194▕
    195▕     $requestItem->refresh();
    196▕

  ───────────────────────────────────────────────────────────────────────────────────────────────────────────────────────  
   FAILED  Tests\Feature\Controllers\LabResultWorkflowControllerTest > it stores reviews and approves parameter-panel l…   
  Expected response status code [201, 301, 302, 303, 307, 308] but received 500.
Failed asserting that false is true.

The following exception occurred during the last request:

Illuminate\Database\Eloquent\MissingAttributeException: The attribute [name] either does not exist or was not retrieved for model [App\Models\SpecimenType]. in C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\laravel\framework\src\Illuminate\Database\Eloquent\Concerns\HasAttributes.php:515
Stack trace:
#0 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\laravel\framework\src\Illuminate\Database\Eloquent\Concerns\HasAttributes.php(495): Illuminate\Database\Eloquent\Model->throwMissingAttributeExceptionIfApplicable('name')
#1 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\laravel\framework\src\Illuminate\Database\Eloquent\Model.php(2423): Illuminate\Database\Eloquent\Model->getAttribute('name')
#2 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\app\Actions\CollectLabSpecimen.php(59): Illuminate\Database\Eloquent\Model->__get('name')
#3 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\laravel\framework\src\Illuminate\Database\Concerns\ManagesTransactions.php(35): App\Actions\CollectLabSpecimen->{closure:App\Actions\CollectLabSpecimen::handle():50}(Object(Illuminate\Database\SQLiteConnection))
#4 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\laravel\framework\src\Illuminate\Database\DatabaseManager.php(491): Illuminate\Database\Connection->transaction(Object(Closure))
#5 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\laravel\framework\src\Illuminate\Support\Facades\Facade.php(363): Illuminate\Database\DatabaseManager->__call('transaction', Array)
#6 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\app\Actions\CollectLabSpecimen.php(50): Illuminate\Support\Facades\Facade::__callStatic('transaction', Array)
#7 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\app\Http\Controllers\LabResultWorkflowController.php(46): App\Actions\CollectLabSpecimen->handle(Object(App\Models\LabRequestItem), Array, '019d5a30-1910-7...')
#8 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\app\Http\Controllers\LabResultWorkflowController.php(131): App\Http\Controllers\LabResultWorkflowController->{closure:App\Http\Controllers\LabResultWorkflowController::collectSample():46}('019d5a30-1910-7...')
#9 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\app\Http\Controllers\LabResultWorkflowController.php(43): App\Http\Controllers\LabResultWorkflowController->handleAction(Object(App\Http\Requests\CollectLabSpecimenRequest), Object(App\Models\LabRequestItem), Object(Closure), 'Sample picked s...')
#10 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\laravel\framework\src\Illuminate\Routing\ControllerDispatcher.php(46): App\Http\Controllers\LabResultWorkflowController->collectSample(Object(App\Http\Requests\CollectLabSpecimenRequest), Object(App\Models\LabRequestItem), Object(App\Actions\CollectLabSpecimen))
#11 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\laravel\framework\src\Illuminate\Routing\Route.php(265): Illuminate\Routing\ControllerDispatcher->dispatch(Object(Illuminate\Routing\Route), Object(App\Http\Controllers\LabResultWorkflowController), 'collectSample')
#12 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\laravel\framework\src\Illuminate\Routing\Route.php(211): Illuminate\Routing\Route->runController()
#13 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\laravel\framework\src\Illuminate\Routing\Router.php(822): Illuminate\Routing\Route->run()
#14 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\laravel\framework\src\Illuminate\Pipeline\Pipeline.php(180): Illuminate\Routing\Router->{closure:Illuminate\Routing\Router::runRouteWithinStack():821}(Object(Illuminate\Http\Request))
#15 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\spatie\laravel-permission\src\Middleware\PermissionMiddleware.php(41): Illuminate\Pipeline\Pipeline->{closure:Illuminate\Pipeline\Pipeline::prepareDestination():178}(Object(Illuminate\Http\Request))
#16 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\laravel\framework\src\Illuminate\Pipeline\Pipeline.php(219): Spatie\Permission\Middleware\PermissionMiddleware->handle(Object(Illuminate\Http\Request), Object(Closure), 'lab_requests.up...')
#17 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\app\Http\Middleware\EnsureActiveBranch.php(51): Illuminate\Pipeline\Pipeline->{closure:{closure:Illuminate\Pipeline\Pipeline::carry():194}:195}(Object(Illuminate\Http\Request))
#18 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\laravel\framework\src\Illuminate\Pipeline\Pipeline.php(219): App\Http\Middleware\EnsureActiveBranch->handle(Object(Illuminate\Http\Request), Object(Closure))
#19 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\laravel\framework\src\Illuminate\Auth\Middleware\EnsureEmailIsVerified.php(41): Illuminate\Pipeline\Pipeline->{closure:{closure:Illuminate\Pipeline\Pipeline::carry():194}:195}(Object(Illuminate\Http\Request))
#20 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\laravel\framework\src\Illuminate\Pipeline\Pipeline.php(219): Illuminate\Auth\Middleware\EnsureEmailIsVerified->handle(Object(Illuminate\Http\Request), Object(Closure))
#21 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\laravel\framework\src\Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets.php(32): Illuminate\Pipeline\Pipeline->{closure:{closure:Illuminate\Pipeline\Pipeline::carry():194}:195}(Object(Illuminate\Http\Request))
#22 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\laravel\framework\src\Illuminate\Pipeline\Pipeline.php(219): Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets->handle(Object(Illuminate\Http\Request), Object(Closure))
#23 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\inertiajs\inertia-laravel\src\Middleware.php(122): Illuminate\Pipeline\Pipeline->{closure:{closure:Illuminate\Pipeline\Pipeline::carry():194}:195}(Object(Illuminate\Http\Request))
#24 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\laravel\framework\src\Illuminate\Pipeline\Pipeline.php(219): Inertia\Middleware->handle(Object(Illuminate\Http\Request), Object(Closure))
#25 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\app\Http\Middleware\HandleAppearance.php(21): Illuminate\Pipeline\Pipeline->{closure:{closure:Illuminate\Pipeline\Pipeline::carry():194}:195}(Object(Illuminate\Http\Request))
#26 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\laravel\framework\src\Illuminate\Pipeline\Pipeline.php(219): App\Http\Middleware\HandleAppearance->handle(Object(Illuminate\Http\Request), Object(Closure))
#27 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\laravel\framework\src\Illuminate\Routing\Middleware\SubstituteBindings.php(50): Illuminate\Pipeline\Pipeline->{closure:{closure:Illuminate\Pipeline\Pipeline::carry():194}:195}(Object(Illuminate\Http\Request))
#28 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\laravel\framework\src\Illuminate\Pipeline\Pipeline.php(219): Illuminate\Routing\Middleware\SubstituteBindings->handle(Object(Illuminate\Http\Request), Object(Closure))
#29 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\laravel\framework\src\Illuminate\Auth\Middleware\Authenticate.php(63): Illuminate\Pipeline\Pipeline->{closure:{closure:Illuminate\Pipeline\Pipeline::carry():194}:195}(Object(Illuminate\Http\Request))
#30 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\laravel\framework\src\Illuminate\Pipeline\Pipeline.php(219): Illuminate\Auth\Middleware\Authenticate->handle(Object(Illuminate\Http\Request), Object(Closure))
#31 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\laravel\framework\src\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken.php(87): Illuminate\Pipeline\Pipeline->{closure:{closure:Illuminate\Pipeline\Pipeline::carry():194}:195}(Object(Illuminate\Http\Request))
#32 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\laravel\framework\src\Illuminate\Pipeline\Pipeline.php(219): Illuminate\Foundation\Http\Middleware\VerifyCsrfToken->handle(Object(Illuminate\Http\Request), Object(Closure))
#33 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\laravel\framework\src\Illuminate\View\Middleware\ShareErrorsFromSession.php(48): Illuminate\Pipeline\Pipeline->{closure:{closure:Illuminate\Pipeline\Pipeline::carry():194}:195}(Object(Illuminate\Http\Request))
#34 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\laravel\framework\src\Illuminate\Pipeline\Pipeline.php(219): Illuminate\View\Middleware\ShareErrorsFromSession->handle(Object(Illuminate\Http\Request), Object(Closure))
#35 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\laravel\framework\src\Illuminate\Session\Middleware\StartSession.php(120): Illuminate\Pipeline\Pipeline->{closure:{closure:Illuminate\Pipeline\Pipeline::carry():194}:195}(Object(Illuminate\Http\Request))
#36 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\laravel\framework\src\Illuminate\Session\Middleware\StartSession.php(63): Illuminate\Session\Middleware\StartSession->handleStatefulRequest(Object(Illuminate\Http\Request), Object(Illuminate\Session\Store), Object(Closure))
#37 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\laravel\framework\src\Illuminate\Pipeline\Pipeline.php(219): Illuminate\Session\Middleware\StartSession->handle(Object(Illuminate\Http\Request), Object(Closure))      
#38 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\laravel\framework\src\Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse.php(36): Illuminate\Pipeline\Pipeline->{closure:{closure:Illuminate\Pipeline\Pipeline::carry():194}:195}(Object(Illuminate\Http\Request))
#39 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\laravel\framework\src\Illuminate\Pipeline\Pipeline.php(219): Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse->handle(Object(Illuminate\Http\Request), Object(Closure))
#40 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\laravel\framework\src\Illuminate\Cookie\Middleware\EncryptCookies.php(74): Illuminate\Pipeline\Pipeline->{closure:{closure:Illuminate\Pipeline\Pipeline::carry():194}:195}(Object(Illuminate\Http\Request))
#41 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\laravel\framework\src\Illuminate\Pipeline\Pipeline.php(219): Illuminate\Cookie\Middleware\EncryptCookies->handle(Object(Illuminate\Http\Request), Object(Closure))     
#42 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\laravel\framework\src\Illuminate\Pipeline\Pipeline.php(137): Illuminate\Pipeline\Pipeline->{closure:{closure:Illuminate\Pipeline\Pipeline::carry():194}:195}(Object(Illuminate\Http\Request))
#43 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\laravel\framework\src\Illuminate\Routing\Router.php(821): Illuminate\Pipeline\Pipeline->then(Object(Closure))
#44 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\laravel\framework\src\Illuminate\Routing\Router.php(800): Illuminate\Routing\Router->runRouteWithinStack(Object(Illuminate\Routing\Route), Object(Illuminate\Http\Request))
#45 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\laravel\framework\src\Illuminate\Routing\Router.php(764): Illuminate\Routing\Router->runRoute(Object(Illuminate\Http\Request), Object(Illuminate\Routing\Route))       
#46 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\laravel\framework\src\Illuminate\Routing\Router.php(753): Illuminate\Routing\Router->dispatchToRoute(Object(Illuminate\Http\Request))
#47 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\laravel\framework\src\Illuminate\Foundation\Http\Kernel.php(200): Illuminate\Routing\Router->dispatch(Object(Illuminate\Http\Request))
#48 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\laravel\framework\src\Illuminate\Pipeline\Pipeline.php(180): Illuminate\Foundation\Http\Kernel->{closure:Illuminate\Foundation\Http\Kernel::dispatchToRouter():197}(Object(Illuminate\Http\Request))
#49 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\laravel\framework\src\Illuminate\Foundation\Http\Middleware\TransformsRequest.php(21): Illuminate\Pipeline\Pipeline->{closure:Illuminate\Pipeline\Pipeline::prepareDestination():178}(Object(Illuminate\Http\Request))
#50 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\laravel\framework\src\Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull.php(31): Illuminate\Foundation\Http\Middleware\TransformsRequest->handle(Object(Illuminate\Http\Request), Object(Closure))
#51 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\laravel\framework\src\Illuminate\Pipeline\Pipeline.php(219): Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull->handle(Object(Illuminate\Http\Request), Object(Closure))
#52 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\laravel\framework\src\Illuminate\Foundation\Http\Middleware\TransformsRequest.php(21): Illuminate\Pipeline\Pipeline->{closure:{closure:Illuminate\Pipeline\Pipeline::carry():194}:195}(Object(Illuminate\Http\Request))
#53 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\laravel\framework\src\Illuminate\Foundation\Http\Middleware\TrimStrings.php(51): Illuminate\Foundation\Http\Middleware\TransformsRequest->handle(Object(Illuminate\Http\Request), Object(Closure))
#54 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\laravel\framework\src\Illuminate\Pipeline\Pipeline.php(219): Illuminate\Foundation\Http\Middleware\TrimStrings->handle(Object(Illuminate\Http\Request), Object(Closure))
#55 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\laravel\framework\src\Illuminate\Http\Middleware\ValidatePostSize.php(27): Illuminate\Pipeline\Pipeline->{closure:{closure:Illuminate\Pipeline\Pipeline::carry():194}:195}(Object(Illuminate\Http\Request))
#56 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\laravel\framework\src\Illuminate\Pipeline\Pipeline.php(219): Illuminate\Http\Middleware\ValidatePostSize->handle(Object(Illuminate\Http\Request), Object(Closure))     
#57 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\laravel\framework\src\Illuminate\Foundation\Http\Middleware\PreventRequestsDuringMaintenance.php(109): Illuminate\Pipeline\Pipeline->{closure:{closure:Illuminate\Pipeline\Pipeline::carry():194}:195}(Object(Illuminate\Http\Request))
#58 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\laravel\framework\src\Illuminate\Pipeline\Pipeline.php(219): Illuminate\Foundation\Http\Middleware\PreventRequestsDuringMaintenance->handle(Object(Illuminate\Http\Request), Object(Closure))
#59 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\laravel\framework\src\Illuminate\Http\Middleware\HandleCors.php(61): Illuminate\Pipeline\Pipeline->{closure:{closure:Illuminate\Pipeline\Pipeline::carry():194}:195}(Object(Illuminate\Http\Request))
#60 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\laravel\framework\src\Illuminate\Pipeline\Pipeline.php(219): Illuminate\Http\Middleware\HandleCors->handle(Object(Illuminate\Http\Request), Object(Closure))
#61 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\laravel\framework\src\Illuminate\Http\Middleware\TrustProxies.php(58): Illuminate\Pipeline\Pipeline->{closure:{closure:Illuminate\Pipeline\Pipeline::carry():194}:195}(Object(Illuminate\Http\Request))
#62 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\laravel\framework\src\Illuminate\Pipeline\Pipeline.php(219): Illuminate\Http\Middleware\TrustProxies->handle(Object(Illuminate\Http\Request), Object(Closure))
#63 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\laravel\framework\src\Illuminate\Foundation\Http\Middleware\InvokeDeferredCallbacks.php(22): Illuminate\Pipeline\Pipeline->{closure:{closure:Illuminate\Pipeline\Pipeline::carry():194}:195}(Object(Illuminate\Http\Request))
#64 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\laravel\framework\src\Illuminate\Pipeline\Pipeline.php(219): Illuminate\Foundation\Http\Middleware\InvokeDeferredCallbacks->handle(Object(Illuminate\Http\Request), Object(Closure))
#65 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\laravel\framework\src\Illuminate\Http\Middleware\ValidatePathEncoding.php(26): Illuminate\Pipeline\Pipeline->{closure:{closure:Illuminate\Pipeline\Pipeline::carry():194}:195}(Object(Illuminate\Http\Request))
#66 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\laravel\framework\src\Illuminate\Pipeline\Pipeline.php(219): Illuminate\Http\Middleware\ValidatePathEncoding->handle(Object(Illuminate\Http\Request), Object(Closure)) 
#67 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\laravel\framework\src\Illuminate\Pipeline\Pipeline.php(137): Illuminate\Pipeline\Pipeline->{closure:{closure:Illuminate\Pipeline\Pipeline::carry():194}:195}(Object(Illuminate\Http\Request))
#68 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\laravel\framework\src\Illuminate\Foundation\Http\Kernel.php(175): Illuminate\Pipeline\Pipeline->then(Object(Closure))
#69 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\laravel\framework\src\Illuminate\Foundation\Http\Kernel.php(144): Illuminate\Foundation\Http\Kernel->sendRequestThroughRouter(Object(Illuminate\Http\Request))
#70 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\laravel\framework\src\Illuminate\Foundation\Testing\Concerns\MakesHttpRequests.php(607): Illuminate\Foundation\Http\Kernel->handle(Object(Illuminate\Http\Request))    
#71 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\laravel\framework\src\Illuminate\Foundation\Testing\Concerns\MakesHttpRequests.php(397): Illuminate\Foundation\Testing\TestCase->call('POST', 'http://localhos...', Array, Array, Array, Array)
#72 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\tests\Feature\Controllers\LabResultWorkflowControllerTest.php(211): Illuminate\Foundation\Testing\TestCase->post('http://localhos...', Array)
#73 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\pestphp\pest\src\Factories\TestCaseMethodFactory.php(172): P\Tests\Feature\Controllers\LabResultWorkflowControllerTest->{closure:C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\tests\Feature\Controllers\LabResultWorkflowControllerTest.php:203}()
#74 [internal function]: P\Tests\Feature\Controllers\LabResultWorkflowControllerTest->{closure:Pest\Factories\TestCaseMethodFactory::getClosure():162}()
#75 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\pestphp\pest\src\Concerns\Testable.php(429): call_user_func_array(Object(Closure), Array)
#76 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\pestphp\pest\src\Support\ExceptionTrace.php(26): P\Tests\Feature\Controllers\LabResultWorkflowControllerTest->{closure:Pest\Concerns\Testable::__callClosure():429}()  
#77 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\pestphp\pest\src\Concerns\Testable.php(429): Pest\Support\ExceptionTrace::ensure(Object(Closure))
#78 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\pestphp\pest\src\Concerns\Testable.php(331): P\Tests\Feature\Controllers\LabResultWorkflowControllerTest->__callClosure(Object(Closure), Array)
#79 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\pestphp\pest\src\Factories\TestCaseFactory.php(170) : eval()'d code(26): P\Tests\Feature\Controllers\LabResultWorkflowControllerTest->__runTest(Object(Closure))       
#80 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\phpunit\phpunit\src\Framework\TestCase.php(1332): P\Tests\Feature\Controllers\LabResultWorkflowControllerTest->__pest_evaluable_it_stores_reviews_and_approves_parameter_panel_lab_results()
#81 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\phpunit\phpunit\src\Framework\TestCase.php(519): PHPUnit\Framework\TestCase->runTest()
#82 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\phpunit\phpunit\src\Framework\TestRunner\TestRunner.php(99): PHPUnit\Framework\TestCase->runBare()
#83 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\phpunit\phpunit\src\Framework\TestCase.php(359): PHPUnit\Framework\TestRunner->run(Object(P\Tests\Feature\Controllers\LabResultWorkflowControllerTest))
#84 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\phpunit\phpunit\src\Framework\TestSuite.php(374): PHPUnit\Framework\TestCase->run()
#85 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\phpunit\phpunit\src\Framework\TestSuite.php(374): PHPUnit\Framework\TestSuite->run()
#86 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\phpunit\phpunit\src\Framework\TestSuite.php(374): PHPUnit\Framework\TestSuite->run()
#87 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\phpunit\phpunit\src\TextUI\TestRunner.php(64): PHPUnit\Framework\TestSuite->run()
#88 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\phpunit\phpunit\src\TextUI\Application.php(229): PHPUnit\TextUI\TestRunner->run(Object(PHPUnit\TextUI\Configuration\Configuration), Object(PHPUnit\Runner\ResultCache\DefaultResultCache), Object(PHPUnit\Framework\TestSuite))
#89 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\pestphp\pest\src\Kernel.php(103): PHPUnit\TextUI\Application->run(Array)
#90 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\pestphp\pest\bin\pest(184): Pest\Kernel->handle(Array, Array)
#91 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\pestphp\pest\bin\pest(192): {closure:C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\pestphp\pest\bin\pest:18}()
#92 {main}

----------------------------------------------------------------------------------

The attribute [name] either does not exist or was not retrieved for model [App\Models\SpecimenType].

  at tests\Feature\Controllers\LabResultWorkflowControllerTest.php:214
    210▕         ->actingAs($user)
    211▕         ->post(route('laboratory.request-items.collect-sample', $requestItem), [
    212▕             'specimen_type_id' => $specimenType->id,
    213▕         ])
  ➜ 214▕         ->assertRedirectToRoute('laboratory.request-items.show', $requestItem);
    215▕
    216▕     $this->withSession(['active_branch_id' => $branch->id])
    217▕         ->actingAs($user)
    218▕         ->post(route('laboratory.request-items.results.store', $requestItem), [

  ───────────────────────────────────────────────────────────────────────────────────────────────────────────────────────  
   FAILED  Tests\Feature\Controllers\LabResultWorkflowControllerTest > it moves a request item between the incoming and…   
  Expected response status code [201, 301, 302, 303, 307, 308] but received 500.
Failed asserting that false is true.

The following exception occurred during the last request:

Illuminate\Database\Eloquent\MissingAttributeException: The attribute [name] either does not exist or was not retrieved for model [App\Models\SpecimenType]. in C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\laravel\framework\src\Illuminate\Database\Eloquent\Concerns\HasAttributes.php:515
Stack trace:
#0 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\laravel\framework\src\Illuminate\Database\Eloquent\Concerns\HasAttributes.php(495): Illuminate\Database\Eloquent\Model->throwMissingAttributeExceptionIfApplicable('name')
#1 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\laravel\framework\src\Illuminate\Database\Eloquent\Model.php(2423): Illuminate\Database\Eloquent\Model->getAttribute('name')
#2 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\app\Actions\CollectLabSpecimen.php(59): Illuminate\Database\Eloquent\Model->__get('name')
#3 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\laravel\framework\src\Illuminate\Database\Concerns\ManagesTransactions.php(35): App\Actions\CollectLabSpecimen->{closure:App\Actions\CollectLabSpecimen::handle():50}(Object(Illuminate\Database\SQLiteConnection))
#4 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\laravel\framework\src\Illuminate\Database\DatabaseManager.php(491): Illuminate\Database\Connection->transaction(Object(Closure))
#5 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\laravel\framework\src\Illuminate\Support\Facades\Facade.php(363): Illuminate\Database\DatabaseManager->__call('transaction', Array)
#6 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\app\Actions\CollectLabSpecimen.php(50): Illuminate\Support\Facades\Facade::__callStatic('transaction', Array)
#7 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\app\Http\Controllers\LabResultWorkflowController.php(46): App\Actions\CollectLabSpecimen->handle(Object(App\Models\LabRequestItem), Array, '019d5a31-bed8-7...')
#8 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\app\Http\Controllers\LabResultWorkflowController.php(131): App\Http\Controllers\LabResultWorkflowController->{closure:App\Http\Controllers\LabResultWorkflowController::collectSample():46}('019d5a31-bed8-7...')
#9 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\app\Http\Controllers\LabResultWorkflowController.php(43): App\Http\Controllers\LabResultWorkflowController->handleAction(Object(App\Http\Requests\CollectLabSpecimenRequest), Object(App\Models\LabRequestItem), Object(Closure), 'Sample picked s...')
#10 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\laravel\framework\src\Illuminate\Routing\ControllerDispatcher.php(46): App\Http\Controllers\LabResultWorkflowController->collectSample(Object(App\Http\Requests\CollectLabSpecimenRequest), Object(App\Models\LabRequestItem), Object(App\Actions\CollectLabSpecimen))
#11 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\laravel\framework\src\Illuminate\Routing\Route.php(265): Illuminate\Routing\ControllerDispatcher->dispatch(Object(Illuminate\Routing\Route), Object(App\Http\Controllers\LabResultWorkflowController), 'collectSample')
#12 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\laravel\framework\src\Illuminate\Routing\Route.php(211): Illuminate\Routing\Route->runController()
#13 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\laravel\framework\src\Illuminate\Routing\Router.php(822): Illuminate\Routing\Route->run()
#14 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\laravel\framework\src\Illuminate\Pipeline\Pipeline.php(180): Illuminate\Routing\Router->{closure:Illuminate\Routing\Router::runRouteWithinStack():821}(Object(Illuminate\Http\Request))
#15 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\spatie\laravel-permission\src\Middleware\PermissionMiddleware.php(41): Illuminate\Pipeline\Pipeline->{closure:Illuminate\Pipeline\Pipeline::prepareDestination():178}(Object(Illuminate\Http\Request))
#16 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\laravel\framework\src\Illuminate\Pipeline\Pipeline.php(219): Spatie\Permission\Middleware\PermissionMiddleware->handle(Object(Illuminate\Http\Request), Object(Closure), 'lab_requests.up...')
#17 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\app\Http\Middleware\EnsureActiveBranch.php(51): Illuminate\Pipeline\Pipeline->{closure:{closure:Illuminate\Pipeline\Pipeline::carry():194}:195}(Object(Illuminate\Http\Request))
#18 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\laravel\framework\src\Illuminate\Pipeline\Pipeline.php(219): App\Http\Middleware\EnsureActiveBranch->handle(Object(Illuminate\Http\Request), Object(Closure))
#19 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\laravel\framework\src\Illuminate\Auth\Middleware\EnsureEmailIsVerified.php(41): Illuminate\Pipeline\Pipeline->{closure:{closure:Illuminate\Pipeline\Pipeline::carry():194}:195}(Object(Illuminate\Http\Request))
#20 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\laravel\framework\src\Illuminate\Pipeline\Pipeline.php(219): Illuminate\Auth\Middleware\EnsureEmailIsVerified->handle(Object(Illuminate\Http\Request), Object(Closure))
#21 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\laravel\framework\src\Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets.php(32): Illuminate\Pipeline\Pipeline->{closure:{closure:Illuminate\Pipeline\Pipeline::carry():194}:195}(Object(Illuminate\Http\Request))
#22 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\laravel\framework\src\Illuminate\Pipeline\Pipeline.php(219): Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets->handle(Object(Illuminate\Http\Request), Object(Closure))
#23 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\inertiajs\inertia-laravel\src\Middleware.php(122): Illuminate\Pipeline\Pipeline->{closure:{closure:Illuminate\Pipeline\Pipeline::carry():194}:195}(Object(Illuminate\Http\Request))
#24 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\laravel\framework\src\Illuminate\Pipeline\Pipeline.php(219): Inertia\Middleware->handle(Object(Illuminate\Http\Request), Object(Closure))
#25 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\app\Http\Middleware\HandleAppearance.php(21): Illuminate\Pipeline\Pipeline->{closure:{closure:Illuminate\Pipeline\Pipeline::carry():194}:195}(Object(Illuminate\Http\Request))
#26 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\laravel\framework\src\Illuminate\Pipeline\Pipeline.php(219): App\Http\Middleware\HandleAppearance->handle(Object(Illuminate\Http\Request), Object(Closure))
#27 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\laravel\framework\src\Illuminate\Routing\Middleware\SubstituteBindings.php(50): Illuminate\Pipeline\Pipeline->{closure:{closure:Illuminate\Pipeline\Pipeline::carry():194}:195}(Object(Illuminate\Http\Request))
#28 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\laravel\framework\src\Illuminate\Pipeline\Pipeline.php(219): Illuminate\Routing\Middleware\SubstituteBindings->handle(Object(Illuminate\Http\Request), Object(Closure))
#29 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\laravel\framework\src\Illuminate\Auth\Middleware\Authenticate.php(63): Illuminate\Pipeline\Pipeline->{closure:{closure:Illuminate\Pipeline\Pipeline::carry():194}:195}(Object(Illuminate\Http\Request))
#30 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\laravel\framework\src\Illuminate\Pipeline\Pipeline.php(219): Illuminate\Auth\Middleware\Authenticate->handle(Object(Illuminate\Http\Request), Object(Closure))
#31 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\laravel\framework\src\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken.php(87): Illuminate\Pipeline\Pipeline->{closure:{closure:Illuminate\Pipeline\Pipeline::carry():194}:195}(Object(Illuminate\Http\Request))
#32 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\laravel\framework\src\Illuminate\Pipeline\Pipeline.php(219): Illuminate\Foundation\Http\Middleware\VerifyCsrfToken->handle(Object(Illuminate\Http\Request), Object(Closure))
#33 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\laravel\framework\src\Illuminate\View\Middleware\ShareErrorsFromSession.php(48): Illuminate\Pipeline\Pipeline->{closure:{closure:Illuminate\Pipeline\Pipeline::carry():194}:195}(Object(Illuminate\Http\Request))
#34 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\laravel\framework\src\Illuminate\Pipeline\Pipeline.php(219): Illuminate\View\Middleware\ShareErrorsFromSession->handle(Object(Illuminate\Http\Request), Object(Closure))
#35 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\laravel\framework\src\Illuminate\Session\Middleware\StartSession.php(120): Illuminate\Pipeline\Pipeline->{closure:{closure:Illuminate\Pipeline\Pipeline::carry():194}:195}(Object(Illuminate\Http\Request))
#36 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\laravel\framework\src\Illuminate\Session\Middleware\StartSession.php(63): Illuminate\Session\Middleware\StartSession->handleStatefulRequest(Object(Illuminate\Http\Request), Object(Illuminate\Session\Store), Object(Closure))
#37 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\laravel\framework\src\Illuminate\Pipeline\Pipeline.php(219): Illuminate\Session\Middleware\StartSession->handle(Object(Illuminate\Http\Request), Object(Closure))      
#38 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\laravel\framework\src\Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse.php(36): Illuminate\Pipeline\Pipeline->{closure:{closure:Illuminate\Pipeline\Pipeline::carry():194}:195}(Object(Illuminate\Http\Request))
#39 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\laravel\framework\src\Illuminate\Pipeline\Pipeline.php(219): Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse->handle(Object(Illuminate\Http\Request), Object(Closure))
#40 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\laravel\framework\src\Illuminate\Cookie\Middleware\EncryptCookies.php(74): Illuminate\Pipeline\Pipeline->{closure:{closure:Illuminate\Pipeline\Pipeline::carry():194}:195}(Object(Illuminate\Http\Request))
#41 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\laravel\framework\src\Illuminate\Pipeline\Pipeline.php(219): Illuminate\Cookie\Middleware\EncryptCookies->handle(Object(Illuminate\Http\Request), Object(Closure))     
#42 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\laravel\framework\src\Illuminate\Pipeline\Pipeline.php(137): Illuminate\Pipeline\Pipeline->{closure:{closure:Illuminate\Pipeline\Pipeline::carry():194}:195}(Object(Illuminate\Http\Request))
#43 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\laravel\framework\src\Illuminate\Routing\Router.php(821): Illuminate\Pipeline\Pipeline->then(Object(Closure))
#44 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\laravel\framework\src\Illuminate\Routing\Router.php(800): Illuminate\Routing\Router->runRouteWithinStack(Object(Illuminate\Routing\Route), Object(Illuminate\Http\Request))
#45 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\laravel\framework\src\Illuminate\Routing\Router.php(764): Illuminate\Routing\Router->runRoute(Object(Illuminate\Http\Request), Object(Illuminate\Routing\Route))       
#46 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\laravel\framework\src\Illuminate\Routing\Router.php(753): Illuminate\Routing\Router->dispatchToRoute(Object(Illuminate\Http\Request))
#47 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\laravel\framework\src\Illuminate\Foundation\Http\Kernel.php(200): Illuminate\Routing\Router->dispatch(Object(Illuminate\Http\Request))
#48 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\laravel\framework\src\Illuminate\Pipeline\Pipeline.php(180): Illuminate\Foundation\Http\Kernel->{closure:Illuminate\Foundation\Http\Kernel::dispatchToRouter():197}(Object(Illuminate\Http\Request))
#49 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\laravel\framework\src\Illuminate\Foundation\Http\Middleware\TransformsRequest.php(21): Illuminate\Pipeline\Pipeline->{closure:Illuminate\Pipeline\Pipeline::prepareDestination():178}(Object(Illuminate\Http\Request))
#50 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\laravel\framework\src\Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull.php(31): Illuminate\Foundation\Http\Middleware\TransformsRequest->handle(Object(Illuminate\Http\Request), Object(Closure))
#51 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\laravel\framework\src\Illuminate\Pipeline\Pipeline.php(219): Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull->handle(Object(Illuminate\Http\Request), Object(Closure))
#52 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\laravel\framework\src\Illuminate\Foundation\Http\Middleware\TransformsRequest.php(21): Illuminate\Pipeline\Pipeline->{closure:{closure:Illuminate\Pipeline\Pipeline::carry():194}:195}(Object(Illuminate\Http\Request))
#53 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\laravel\framework\src\Illuminate\Foundation\Http\Middleware\TrimStrings.php(51): Illuminate\Foundation\Http\Middleware\TransformsRequest->handle(Object(Illuminate\Http\Request), Object(Closure))
#54 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\laravel\framework\src\Illuminate\Pipeline\Pipeline.php(219): Illuminate\Foundation\Http\Middleware\TrimStrings->handle(Object(Illuminate\Http\Request), Object(Closure))
#55 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\laravel\framework\src\Illuminate\Http\Middleware\ValidatePostSize.php(27): Illuminate\Pipeline\Pipeline->{closure:{closure:Illuminate\Pipeline\Pipeline::carry():194}:195}(Object(Illuminate\Http\Request))
#56 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\laravel\framework\src\Illuminate\Pipeline\Pipeline.php(219): Illuminate\Http\Middleware\ValidatePostSize->handle(Object(Illuminate\Http\Request), Object(Closure))     
#57 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\laravel\framework\src\Illuminate\Foundation\Http\Middleware\PreventRequestsDuringMaintenance.php(109): Illuminate\Pipeline\Pipeline->{closure:{closure:Illuminate\Pipeline\Pipeline::carry():194}:195}(Object(Illuminate\Http\Request))
#58 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\laravel\framework\src\Illuminate\Pipeline\Pipeline.php(219): Illuminate\Foundation\Http\Middleware\PreventRequestsDuringMaintenance->handle(Object(Illuminate\Http\Request), Object(Closure))
#59 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\laravel\framework\src\Illuminate\Http\Middleware\HandleCors.php(61): Illuminate\Pipeline\Pipeline->{closure:{closure:Illuminate\Pipeline\Pipeline::carry():194}:195}(Object(Illuminate\Http\Request))
#60 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\laravel\framework\src\Illuminate\Pipeline\Pipeline.php(219): Illuminate\Http\Middleware\HandleCors->handle(Object(Illuminate\Http\Request), Object(Closure))
#61 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\laravel\framework\src\Illuminate\Http\Middleware\TrustProxies.php(58): Illuminate\Pipeline\Pipeline->{closure:{closure:Illuminate\Pipeline\Pipeline::carry():194}:195}(Object(Illuminate\Http\Request))
#62 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\laravel\framework\src\Illuminate\Pipeline\Pipeline.php(219): Illuminate\Http\Middleware\TrustProxies->handle(Object(Illuminate\Http\Request), Object(Closure))
#63 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\laravel\framework\src\Illuminate\Foundation\Http\Middleware\InvokeDeferredCallbacks.php(22): Illuminate\Pipeline\Pipeline->{closure:{closure:Illuminate\Pipeline\Pipeline::carry():194}:195}(Object(Illuminate\Http\Request))
#64 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\laravel\framework\src\Illuminate\Pipeline\Pipeline.php(219): Illuminate\Foundation\Http\Middleware\InvokeDeferredCallbacks->handle(Object(Illuminate\Http\Request), Object(Closure))
#65 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\laravel\framework\src\Illuminate\Http\Middleware\ValidatePathEncoding.php(26): Illuminate\Pipeline\Pipeline->{closure:{closure:Illuminate\Pipeline\Pipeline::carry():194}:195}(Object(Illuminate\Http\Request))
#66 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\laravel\framework\src\Illuminate\Pipeline\Pipeline.php(219): Illuminate\Http\Middleware\ValidatePathEncoding->handle(Object(Illuminate\Http\Request), Object(Closure)) 
#67 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\laravel\framework\src\Illuminate\Pipeline\Pipeline.php(137): Illuminate\Pipeline\Pipeline->{closure:{closure:Illuminate\Pipeline\Pipeline::carry():194}:195}(Object(Illuminate\Http\Request))
#68 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\laravel\framework\src\Illuminate\Foundation\Http\Kernel.php(175): Illuminate\Pipeline\Pipeline->then(Object(Closure))
#69 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\laravel\framework\src\Illuminate\Foundation\Http\Kernel.php(144): Illuminate\Foundation\Http\Kernel->sendRequestThroughRouter(Object(Illuminate\Http\Request))
#70 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\laravel\framework\src\Illuminate\Foundation\Testing\Concerns\MakesHttpRequests.php(607): Illuminate\Foundation\Http\Kernel->handle(Object(Illuminate\Http\Request))    
#71 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\laravel\framework\src\Illuminate\Foundation\Testing\Concerns\MakesHttpRequests.php(397): Illuminate\Foundation\Testing\TestCase->call('POST', 'http://localhos...', Array, Array, Array, Array)
#72 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\tests\Feature\Controllers\LabResultWorkflowControllerTest.php(284): Illuminate\Foundation\Testing\TestCase->post('http://localhos...', Array)
#73 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\pestphp\pest\src\Factories\TestCaseMethodFactory.php(172): P\Tests\Feature\Controllers\LabResultWorkflowControllerTest->{closure:C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\tests\Feature\Controllers\LabResultWorkflowControllerTest.php:266}()
#74 [internal function]: P\Tests\Feature\Controllers\LabResultWorkflowControllerTest->{closure:Pest\Factories\TestCaseMethodFactory::getClosure():162}()
#75 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\pestphp\pest\src\Concerns\Testable.php(429): call_user_func_array(Object(Closure), Array)
#76 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\pestphp\pest\src\Support\ExceptionTrace.php(26): P\Tests\Feature\Controllers\LabResultWorkflowControllerTest->{closure:Pest\Concerns\Testable::__callClosure():429}()  
#77 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\pestphp\pest\src\Concerns\Testable.php(429): Pest\Support\ExceptionTrace::ensure(Object(Closure))
#78 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\pestphp\pest\src\Concerns\Testable.php(331): P\Tests\Feature\Controllers\LabResultWorkflowControllerTest->__callClosure(Object(Closure), Array)
#79 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\pestphp\pest\src\Factories\TestCaseFactory.php(170) : eval()'d code(35): P\Tests\Feature\Controllers\LabResultWorkflowControllerTest->__runTest(Object(Closure))       
#80 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\phpunit\phpunit\src\Framework\TestCase.php(1332): P\Tests\Feature\Controllers\LabResultWorkflowControllerTest->__pest_evaluable_it_moves_a_request_item_between_the_incoming_and_enter_results_queues_after_sample_picking()
#81 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\phpunit\phpunit\src\Framework\TestCase.php(519): PHPUnit\Framework\TestCase->runTest()
#82 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\phpunit\phpunit\src\Framework\TestRunner\TestRunner.php(99): PHPUnit\Framework\TestCase->runBare()
#83 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\phpunit\phpunit\src\Framework\TestCase.php(359): PHPUnit\Framework\TestRunner->run(Object(P\Tests\Feature\Controllers\LabResultWorkflowControllerTest))
#84 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\phpunit\phpunit\src\Framework\TestSuite.php(374): PHPUnit\Framework\TestCase->run()
#85 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\phpunit\phpunit\src\Framework\TestSuite.php(374): PHPUnit\Framework\TestSuite->run()
#86 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\phpunit\phpunit\src\Framework\TestSuite.php(374): PHPUnit\Framework\TestSuite->run()
#87 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\phpunit\phpunit\src\TextUI\TestRunner.php(64): PHPUnit\Framework\TestSuite->run()
#88 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\phpunit\phpunit\src\TextUI\Application.php(229): PHPUnit\TextUI\TestRunner->run(Object(PHPUnit\TextUI\Configuration\Configuration), Object(PHPUnit\Runner\ResultCache\DefaultResultCache), Object(PHPUnit\Framework\TestSuite))
#89 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\pestphp\pest\src\Kernel.php(103): PHPUnit\TextUI\Application->run(Array)
#90 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\pestphp\pest\bin\pest(184): Pest\Kernel->handle(Array, Array)
#91 C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\pestphp\pest\bin\pest(192): {closure:C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\vendor\pestphp\pest\bin\pest:18}()
#92 {main}

----------------------------------------------------------------------------------

The attribute [name] either does not exist or was not retrieved for model [App\Models\SpecimenType].

  at tests\Feature\Controllers\LabResultWorkflowControllerTest.php:287
    283▕         ->actingAs($user)
    284▕         ->post(route('laboratory.request-items.collect-sample', $requestItem), [
    285▕             'specimen_type_id' => $specimenType->id,
    286▕         ])
  ➜ 287▕         ->assertRedirectToRoute('laboratory.request-items.show', $requestItem);
    288▕
    289▕     $this->withSession(['active_branch_id' => $branch->id])
    290▕         ->actingAs($user)
    291▕         ->get(route('laboratory.enter-results.index'))

  ───────────────────────────────────────────────────────────────────────────────────────────────────────────────────────  
   FAILED  Tests\Feature\Controllers\PermissionEnforcementTest > `Appointment action permissions` → it forbids and allo…   
  Expected response status code [201, 301, 302, 303, 307, 308] but received 403.
Failed asserting that false is true.

  at tests\Feature\Controllers\PermissionEnforcementTest.php:907
    903▕ 
    904▕         $response = $this->actingAs($user)
    905▕             ->post(route('appointments.confirm', $appointment));
    906▕
  ➜ 907▕         $response->assertRedirectToRoute('appointments.show', $appointment);
    908▕         $response->assertSessionHas('success', 'Appointment confirmed successfully.');
    909▕
    910▕         expect($appointment->fresh()->status->value)->toBe('confirmed');
    911▕     });

  ───────────────────────────────────────────────────────────────────────────────────────────────────────────────────────  
   FAILED  Tests\Feature\Controllers\SessionControllerTest > it may create a session
  Failed asserting that two strings are equal.
--- Expected
+++ Actual
@@ @@
-'http://localhost/dashboard'
+'http://localhost'

  at tests\Feature\Controllers\SessionControllerTest.php:33
     29▕             'email' => 'test@example.com',
     30▕             'password' => 'password',
     31▕         ]);
     32▕
  ➜  33▕     $response->assertRedirectToRoute('dashboard');
     34▕
     35▕     $this->assertAuthenticatedAs($user);
     36▕ });
     37▕

  ───────────────────────────────────────────────────────────────────────────────────────────────────────────────────────  
   FAILED  Tests\Feature\Controllers\SessionControllerTest > it may create a session with remember me
  Failed asserting that two strings are equal.
--- Expected
+++ Actual
@@ @@
-'http://localhost/dashboard'
+'http://localhost'

  at tests\Feature\Controllers\SessionControllerTest.php:51
     47▕             'password' => 'password',
     48▕             'remember' => true,
     49▕         ]);
     50▕
  ➜  51▕     $response->assertRedirectToRoute('dashboard');
     52▕
     53▕     $this->assertAuthenticatedAs($user);
     54▕ });
     55▕

  ───────────────────────────────────────────────────────────────────────────────────────────────────────────────────────  
   FAILED  Tests\Feature\Controllers\SessionControllerTest > it clears rate limit after successful login
  Failed asserting that two strings are equal.
--- Expected
+++ Actual
@@ @@
-'http://localhost/dashboard'
+'http://localhost'

  at tests\Feature\Controllers\SessionControllerTest.php:187
    183▕             'email' => 'test@example.com',
    184▕             'password' => 'password',
    185▕         ]);
    186▕
  ➜ 187▕     $response->assertRedirectToRoute('dashboard');
    188▕     $this->assertAuthenticatedAs($user);
    189▕ });
    190▕
    191▕ it('dispatches lockout event when rate limit is reached', function (): void {

  ───────────────────────────────────────────────────────────────────────────────────────────────────────────────────────  
   FAILED  Tests\Feature\Controllers\SupplierControllerTest > it deletes a supplier
  Failed asserting that App\Models\Supplier Object #272099 (
    'connection' => 'sqlite',
    'table' => 'suppliers',
    'primaryKey' => 'id',
    'keyType' => 'int',
    'incrementing' => true,
    'with' => Array &0 [],
    'withCount' => Array &1 [],
    'preventsLazyLoading' => false,
    'perPage' => 15,
    'exists' => true,
    'wasRecentlyCreated' => false,
    'escapeWhenCastingToString' => false,
    'attributes' => Array &2 [
        'id' => '019d5a72-21fa-710e-8a0d-e9b3dc4249ee',
        'tenant_id' => '019d5a72-12ba-71fb-a029-10facbece142',
        'name' => 'To Delete',
        'contact_person' => null,
        'email' => null,
        'phone' => null,
        'address' => null,
        'tax_id' => null,
        'notes' => null,
        'is_active' => 1,
        'created_by' => null,
        'updated_by' => null,
        'created_at' => '2026-04-04 21:40:42',
        'updated_at' => '2026-04-04 21:40:42',
        'deleted_at' => '2026-04-04 21:40:42',
    ],
    'original' => Array &3 [
        'id' => '019d5a72-21fa-710e-8a0d-e9b3dc4249ee',
        'tenant_id' => '019d5a72-12ba-71fb-a029-10facbece142',
        'name' => 'To Delete',
        'contact_person' => null,
        'email' => null,
        'phone' => null,
        'address' => null,
        'tax_id' => null,
        'notes' => null,
        'is_active' => 1,
        'created_by' => null,
        'updated_by' => null,
        'created_at' => '2026-04-04 21:40:42',
        'updated_at' => '2026-04-04 21:40:42',
        'deleted_at' => '2026-04-04 21:40:42',
    ],
    'changes' => Array &4 [],
    'previous' => Array &5 [],
    'casts' => Array &6 [
        'tenant_id' => 'string',
        'is_active' => 'boolean',
        'created_by' => 'string',
        'updated_by' => 'string',
        'deleted_at' => 'datetime',
    ],
    'classCastCache' => Array &7 [],
    'attributeCastCache' => Array &8 [],
    'dateFormat' => null,
    'appends' => Array &9 [],
    'dispatchesEvents' => Array &10 [],
    'observables' => Array &11 [],
    'relations' => Array &12 [],
    'touches' => Array &13 [],
    'relationAutoloadCallback' => Closure Object #268455 (
        0 => Closure Object #268455,
    ),
    'relationAutoloadContext' => Illuminate\Database\Eloquent\Collection Object #288725 (
        'items' => Array &14 [
            0 => App\Models\Supplier Object #272099,
        ],
        'escapeWhenCastingToString' => false,
    ),
    'timestamps' => true,
    'usesUniqueIds' => true,
    'hidden' => Array &15 [],
    'visible' => Array &16 [],
    'fillable' => Array &17 [],
    'guarded' => Array &18 [
        0 => '*',
    ],
    'forceDeleting' => false,
) is null.

  at tests\Feature\Controllers\SupplierControllerTest.php:184
    180▕         ->actingAs($user)
    181▕         ->delete(route('suppliers.destroy', $supplier));
    182▕
    183▕     $response->assertRedirectToRoute('suppliers.index');
  ➜ 184▕     expect($supplier->fresh())->toBeNull();
    185▕ });
    186▕
    187▕ it('searches suppliers', function (): void {
    188▕     [$tenant, $branch, $user] = createSupplierTestContext();

  1   tests\Feature\Controllers\SupplierControllerTest.php:184

  ───────────────────────────────────────────────────────────────────────────────────────────────────────────────────────  
   FAILED  Tests\Feature\Controllers\UserProfileControllerTest > it may update profile information
  Failed asserting that two strings are identical.
--- Expected
+++ Actual
@@ @@
-'new@example.com'
+'old@example.com'

  at tests\Feature\Controllers\UserProfileControllerTest.php:33
     29▕         ]);
     30▕
     31▕     $response->assertRedirectToRoute('user-profile.edit');
     32▕
  ➜  33▕     expect($user->refresh()->email)->toBe('new@example.com');
     34▕ });
     35▕
     36▕ it('resets email verification when email changes', function (): void {
     37▕     $user = User::factory()->create([

  1   tests\Feature\Controllers\UserProfileControllerTest.php:33

  ───────────────────────────────────────────────────────────────────────────────────────────────────────────────────────  
   FAILED  Tests\Feature\Controllers\UserProfileControllerTest > it resets email verification when email changes
  Failed asserting that Carbon\CarbonImmutable Object #275382 (
    'endOfTime' => false,
    'startOfTime' => false,
    'constructedObjectId' => '00000000000433b60000000000000000',
    'clock' => null,
    'localMonthsOverflow' => null,
    'localYearsOverflow' => null,
    'localStrictModeEnabled' => null,
    'localHumanDiffOptions' => null,
    'localToStringFormat' => null,
    'localSerializer' => null,
    'localMacros' => null,
    'localGenericMacros' => null,
    'localFormatFunction' => null,
    'localTranslator' => null,
    'dumpProperties' => Array &0 [
        0 => 'date',
        1 => 'timezone_type',
        2 => 'timezone',
    ],
    'dumpLocale' => null,
    'dumpDateProperties' => null,
    'date' => '2026-04-04 21:54:34.000000',
    'timezone_type' => 3,
    'timezone' => 'UTC',
) is null.

  at tests\Feature\Controllers\UserProfileControllerTest.php:50
     46▕         ]);
     47▕
     48▕     $response->assertRedirectToRoute('user-profile.edit');
     49▕
  ➜  50▕     expect($user->refresh()->email_verified_at)->toBeNull();
     51▕ });
     52▕
     53▕ it('keeps email verification when email stays the same', function (): void {
     54▕     $verifiedAt = now();

  1   tests\Feature\Controllers\UserProfileControllerTest.php:50

  ───────────────────────────────────────────────────────────────────────────────────────────────────────────────────────  
   FAILED  Tests\Feature\Controllers\UserProfileControllerTest > it allows keeping same email
  Session has unexpected errors: 

{
    "default": [
        "The name field is required."
    ]
}
Failed asserting that true is false.

  at tests\Feature\Controllers\UserProfileControllerTest.php:136

  at tests\Feature\Controllers\UserProfileControllerTest.php:136
    132▕             'email' => 'test@example.com',
    133▕         ]);
    134▕
    135▕     $response->assertRedirectToRoute('user-profile.edit')
  ➜ 136▕         ->assertSessionDoesntHaveErrors();
    137▕ });
    138▕


  Tests:    16 failed, 229 passed (883 assertions)
  Duration: 9440.37s

PS C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2>



  at tests\Feature\Controllers\UserProfileControllerTest.php:136
    132▕             'email' => 'test@example.com',
    133▕         ]);
    134▕
    135▕     $response->assertRedirectToRoute('user-profile.edit')
  ➜ 136▕         ->assertSessionDoesntHaveErrors();
    137▕ });
    138▕


  Tests:    16 failed, 229 passed (883 assertions)
  Duration: 9440.37s


  at tests\Feature\Controllers\UserProfileControllerTest.php:136
    132▕             'email' => 'test@example.com',
    133▕         ]);
    134▕
    135▕     $response->assertRedirectToRoute('user-profile.edit')
  ➜ 136▕         ->assertSessionDoesntHaveErrors();
    137▕ });
    138▕


  Tests:    16 failed, 229 passed (883 assertions)

  at tests\Feature\Controllers\UserProfileControllerTest.php:136
    132▕             'email' => 'test@example.com',
    133▕         ]);
    134▕
    135▕     $response->assertRedirectToRoute('user-profile.edit')
  ➜ 136▕         ->assertSessionDoesntHaveErrors();
    137▕ });
    138▕


  at tests\Feature\Controllers\UserProfileControllerTest.php:136
    132▕             'email' => 'test@example.com',
    133▕         ]);
    134▕
    135▕     $response->assertRedirectToRoute('user-profile.edit')
  ➜ 136▕         ->assertSessionDoesntHaveErrors();
    137▕ });

  at tests\Feature\Controllers\UserProfileControllerTest.php:136
    132▕             'email' => 'test@example.com',
    133▕         ]);
    134▕
    135▕     $response->assertRedirectToRoute('user-profile.edit')

  at tests\Feature\Controllers\UserProfileControllerTest.php:136
    132▕             'email' => 'test@example.com',
    133▕         ]);

  at tests\Feature\Controllers\UserProfileControllerTest.php:136
    132▕             'email' => 'test@example.com',
    133▕         ]);
    134▕
    132▕             'email' => 'test@example.com',
    133▕         ]);
    134▕
    135▕     $response->assertRedirectToRoute('user-profile.edit')
  ➜ 136▕         ->assertSessionDoesntHaveErrors();
    137▕ });
  ➜ 136▕         ->assertSessionDoesntHaveErrors();
    137▕ });
  ➜ 136▕         ->assertSessionDoesntHaveErrors();
  ➜ 136▕         ->assertSessionDoesntHaveErrors();
  ➜ 136▕         ->assertSessionDoesntHaveErrors();
    137▕ });
    138▕


  Tests:    16 failed, 229 passed (883 assertions)
  Duration: 9440.37s