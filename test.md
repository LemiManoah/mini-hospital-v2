PS C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2> php artisan test --coverage                        
   
   FAIL  Tests\Browser\RolePermissionsTest
  ⨯ it shows permission labels without group prefix on the create role page                                       132.92s  

   FAIL  Tests\Browser\WelcomeTest
  ⨯ it has welcome page                                                                                             6.94s  

   PASS  Tests\Unit\Actions\AssessPatientVisitCompletionTest
  ✓ it blocks visit completion until a triaged visit has a finalized consultation                                   2.88s  
  ✓ it blocks visit completion when downstream consultation orders are still pending                                1.53s  
  ✓ it counts pending facility service orders as blocking downstream work                                           1.47s  
  ✓ it warns when a visit has an unpaid balance and clears the warning after settlement                             2.00s  

   PASS  Tests\Unit\Actions\CreateConsultationOrdersTest
  ✓ it creates a lab request with priced items from the consultation context and syncs a visit charge               2.39s  
  ✓ it uses insurance package prices when syncing lab request charges                                               2.04s  
  ✓ it creates a prescription with multiple drug items                                                              1.75s  
  ✓ it creates an imaging request linked to the consultation                                                        1.47s  
  ✓ it creates a facility service order with consultation context and syncs an insurance-priced charge              1.43s  
  ✓ it creates a facility service order with the service selling price for cash visits                              1.57s  
  ✓ it creates a facility service order without syncing a charge for non-billable services                          1.19s  
  ✓ it prevents duplicate pending facility service orders for the same visit                                        1.76s  
  ✓ it deletes a pending facility service order and its synced charge                                               1.46s  

   PASS  Tests\Unit\Actions\CreateConsultationTest
  ✓ it creates a consultation using triage context and the authenticated clinician                                  1.35s  

   PASS  Tests\Unit\Actions\CreateUserEmailResetNotificationTest
  ✓ it may send password reset notification                                                                         2.29s  
  ✓ it returns throttled status when too many attempts                                                             26.87s  
  ✓ it returns invalid user status for non-existent email                                                           0.86s  

   PASS  Tests\Unit\Actions\CreateUserEmailVerificationNotificationTest
  ✓ it may send email verification notification                                                                     1.74s  

   PASS  Tests\Unit\Actions\CreateUserPasswordTest
  ✓ it may create a new user password                                                                               1.85s  
  ✓ it returns invalid token status for incorrect token                                                             1.63s  
  ✓ it returns invalid user status for non-existent email                                                           0.86s  
  ✓ it updates remember token when resetting password                                                               1.10s  

   PASS  Tests\Unit\Actions\CreateUserTest
  ✓ it may create a user                                                                                            1.07s  

   PASS  Tests\Unit\Actions\CreateVitalSignTest
  ✓ it normalizes numeric strings before calculating derived vital sign values                                      1.06s  

   PASS  Tests\Unit\Actions\DeleteUserTest
  ✓ it may delete a user                                                                                            1.18s  

   PASS  Tests\Unit\Actions\PostGoodsReceiptTest
  ✓ it rejects posting when the goods receipt is no longer draft in storage                                         1.50s  

   PASS  Tests\Unit\Actions\RecalculateVisitBillingTest
  ✓ it recalculates visit billing totals from charges and payments                                                  1.34s  

   PASS  Tests\Unit\Actions\UpdateUserPasswordTest
  ✓ it may update a user password                                                                                   1.17s  

   PASS  Tests\Unit\Actions\UpdateUserTest
  ✓ it may update a user                                                                                            1.57s  
  ✓ it resets email verification and sends notification when email changes                                          0.93s  
  ✓ it keeps email verification and does not send notification when email stays the same                            0.96s  

   FAIL  Tests\Unit\ArchTest
  ✓ preset → php                                                                                                  265.19s  
  ⨯ preset → strict                                                                                               275.54s  
  ✓ preset → security → ignoring ['assert']                                                                        16.40s  
  ✓ controllers                                                                                                    74.49s  

   PASS  Tests\Unit\Middleware\HandleAppearanceTest
  ✓ it shares appearance cookie value with views                                                                    1.43s  
  ✓ it defaults to system when appearance cookie not present                                                        1.14s  
  ✓ it handles light appearance                                                                                     1.25s  
  ✓ it handles system appearance                                                                                    1.14s  

   PASS  Tests\Unit\Middleware\HandleInertiaRequestsTest
  ✓ it shares app name from config                                                                                  1.91s  
  ✓ it shares null user when guest                                                                                  1.29s  
  ✓ it shares authenticated user data                                                                               1.93s  
  ✓ it defaults sidebarOpen to true when no cookie                                                                  1.27s  
  ✓ it sets sidebarOpen to true when cookie is true                                                                 1.42s  
  ✓ it sets sidebarOpen to false when cookie is false                                                               1.26s  
  ✓ it includes parent shared data                                                                                  1.32s  

   FAIL  Tests\Unit\Models\UserTest
  ⨯ to array                                                                                                        1.91s  

   PASS  Tests\Unit\Rules\ValidEmailTest
  ✓ it works with valid email with ('simple@example.com')                                                           1.37s  
  ✓ it works with valid email with ('very.common@example.com')                                                      1.39s  
  ✓ it works with valid email with ('disposable.style.email.with+s…le.com')                                         1.43s  
  ✓ it works with valid email with ('other.email-with-hyphen@example.com')                                          1.47s  
  ✓ it works with valid email with ('x@example.com')                                                                1.37s  
  ✓ it works with valid email with ('example-indeed@strange-example.com')                                           1.29s  
  ✓ it works with valid email with ('admin@mailserver1.com')                                                        1.34s  
  ✓ it works with valid email with ('user.name+tag+sorting@example.com')                                            1.39s  
  ✓ it works with valid email with ('user.name@sub.domain.com')                                                     1.32s  
  ✓ it works with valid email with ('firstname-lastname@example.com')                                               1.39s  
  ✓ it works with valid email with ('1234567890@example.com')                                                       1.42s  
  ✓ it works with valid email with ('user.123@example.com')                                                         1.40s  
  ✓ it works with valid email with ('user123@example.com')                                                          1.27s  
  ✓ it works with valid email with ('9876543210@example.net')                                                       1.38s  
  ✓ it works with valid email with ('test456@domain123.com')                                                        1.43s  
  ✓ it works with valid email with ('a.very.long.email.address.but…le.com')                                         1.49s  
  ✓ it works with valid email with ('another.really.long.email.add….co.uk')                                         1.28s  
  ✓ it works with valid email with ('longlocalpart1234567890123456…le.com')                                         1.38s  
  ✓ it works with valid email with ('superlongemailaddresswith1234…le.org')                                         1.32s  
  ✓ it works with valid email with ('excessive-length-testing-allo…le.com')                                         1.41s  
  ✓ it works with valid email with ('user@ex-ample.com')                                                            1.27s  
  ✓ it works with valid email with ('user@mail.example.com')                                                        1.40s  
  ✓ it works with valid email with ('contact@support.company.com')                                                  1.30s  
  ✓ it works with valid email with ('info@help.docs.example.com')                                                   1.40s  
  ✓ it works with valid email with ('customer.service@global.enterprise.org')                                       1.47s  
  ✓ it works with valid email with ('feedback@eu.store.example.net')                                                1.41s  
  ✓ it works with valid email with ('user@company.app')                                                             1.36s  
  ✓ it works with valid email with ('support@business.dev')                                                         1.32s  
  ✓ it works with valid email with ('test@something.xyz')                                                           1.38s  
  ✓ it works with valid email with ('email@custom.tld')                                                             1.29s  
  ✓ it works with valid email with ('person@organization.online')                                                   1.48s  
  ✓ it works with valid email with ('user@domain.museum')                                                           1.31s  
  ✓ it works with valid email with ('info@charity.foundation')                                                      1.37s  
  ✓ it works with valid email with ('admin@website.travel')                                                         1.29s  
  ✓ it works with valid email with ('sales@company.agency')                                                         1.35s  
  ✓ it works with valid email with ('team@startup.tech')                                                            1.25s  
  ✓ it fails with invalid email with ('R@r.com')                                                                    1.40s  
  ✓ it fails with invalid email with ('r@R.com')                                                                    1.36s  
  ✓ it fails with invalid email with ('@example.com')                                                               1.39s  
  ✓ it fails with invalid email with ('user@')                                                                      1.33s  
  ✓ it fails with invalid email with ('user@.com')                                                                  1.46s  
  ✓ it fails with invalid email with ('user@.example')                                                              1.39s  
  ✓ it fails with invalid email with ('user@.example.com')                                                          1.35s  
  ✓ it fails with invalid email with ('user@sub..example.com')                                                      1.40s  
  ✓ it fails with invalid email with ('user')                                                                       1.30s  
  ✓ it fails with invalid email with ('')                                                                           1.52s  
  ✓ it fails with invalid email with ('user@123.123.123.123')                                                       1.52s  
  ✓ it fails with invalid email with ('user@[192.168.1.1]')                                                         1.45s  
  ✓ it fails with invalid email with ('user@[IPv6:2001:db8::1]')                                                    1.29s  
  ✓ it fails with invalid email with ('"user@with-quotes"@example.com')                                             1.36s  
  ✓ it fails with invalid email with (''user@with-quotes'@example.com')                                             1.30s  
  ✓ it fails with invalid email with ('"very.unusual.@.email"@example.com')                                         1.27s  
  ✓ it fails with invalid email with ('"quoted.local@part"@example.com')                                            1.38s  
  ✓ it fails with invalid email with ('"user name"@example.com')                                                    1.38s  
  ✓ it fails with invalid email with ('üñîçødé@example.com')                                                        1.40s  
  ✓ it fails with invalid email with ('δοκιμή@παράδειγμα.ελ')                                                       1.43s  
  ✓ it fails with invalid email with ('测试@测试.中国')                                                             1.37s  
  ✓ it fails with invalid email with ('пример@пример.рус')                                                          1.51s  
  ✓ it fails with invalid email with ('उपयोगकर्ता@उदाहरण.भारत')                                                     1.33s  
  ✓ it fails with invalid email with ('mat@me')                                                                     1.38s  
  ✓ it fails with invalid email with ('user@localserver')                                                           1.25s  
  ✓ it fails with invalid email with ('user@localdomain')                                                           1.34s  
  ✓ it fails with invalid email with ('user@sub.-domain.com')                                                       1.25s  
  ✓ it fails with invalid email with ('𝓊𝓃𝒾𝒸ℴ𝒹ℯ@𝒹ℴ𝓂𝒶𝒾𝓃.𝒸ℴ𝓂')                                                         1.43s  

   PASS  Tests\Feature\Controllers\BranchIsolationTest
  ✓ it redirects tenant users to branch switcher when multiple branches and none selected                         144.08s  
  ✓ it allows tenant admins to open facility branch administration without an active branch selected              147.98s  
  ✓ it allows switching to an authorized branch and stores it in session                                          118.42s  
  ✓ it forbids switching to an inactive branch                                                                    140.48s  
  ✓ it forbids opening an appointment from a different active branch                                               89.03s  
  ✓ it forbids opening a visit from a different active branch                                                     139.91s  
  ✓ it forbids editing a clinic from a different active branch                                                     94.45s  
  ✓ it forbids editing a doctor schedule from a different active branch                                            99.50s  
  ✓ it forbids non-support users from opening the facility switcher                                                 1.48s  
  ✓ it forbids non-support users from switching facility context                                                    1.57s  
  ✓ it allows support users to switch tenant context and clears active branch selection                            90.59s  

   PASS  Tests\Feature\Controllers\GoodsReceiptControllerTest
  ✓ it lists goods receipts for authorized user                                                                   120.43s  
  ✓ it denies goods receipt index without permission                                                              123.50s  
  ✓ it creates a goods receipt                                                                                    130.63s  
  ✓ it rejects receipt items from a different purchase order                                                      126.00s  
  ✓ it rejects receipt items whose inventory item does not match the purchase order item                          127.27s  
  ✓ it posts a goods receipt and updates PO item quantities                                                        88.66s  
  ✓ it fully receiving all items marks PO as received                                                              84.79s  
  ✓ it prevents posting an already posted goods receipt                                                            78.27s  
  ✓ it prevents creating goods receipt against a draft PO                                                          93.00s  
  ✓ it shows a goods receipt detail page                                                                          129.65s  

   PASS  Tests\Feature\Controllers\LabReferenceLookupControllerTest
  ✓ it creates and lists tenant lab test categories                                                               160.06s  
  ✓ it prevents editing default lab test categories                                                               113.34s  
  ✓ it updates and deletes a tenant specimen type                                                                 121.62s  
  ✓ it creates and lists result types with code search                                                            117.38s  
  ✓ it blocks deleting a result type that is already used by a lab test                                           114.36s  

   FAIL  Tests\Feature\Controllers\LabRequestItemConsumableControllerTest
  ⨯ it shows the laboratory worklist to authorized users                                                          112.24s  
  ✓ it shows the laboratory dashboard to authorized users                                                          99.61s  
  ✓ it records and removes consumable usage while syncing actual cost                                              85.40s  

   FAIL  Tests\Feature\Controllers\LabResultWorkflowControllerTest
  ⨯ it picks a sample for a laboratory request item from the incoming queue                                       111.65s  
  ⨯ it stores reviews and approves parameter-panel lab results                                                    102.87s  
  ⨯ it moves a request item between the incoming and enter-results queues after sample picking                    107.30s  

   PASS  Tests\Feature\Controllers\LabTestCatalogControllerTest
  ✓ it forbids users without lab test catalog view permission and allows authorized index access                   91.47s  
  ✓ it forbids users without lab test catalog create permission and allows authorized create access               117.98s  
  ✓ it lists lab tests for authorized users and supports search                                                    86.69s  
  ✓ it creates a lab test catalog entry with lookup-backed relationships                                           71.35s  
  ✓ it updates a lab test catalog entry                                                                            98.02s  
  ✓ it deletes an unreferenced lab test catalog entry                                                             125.59s  
  ✓ it blocks deleting a referenced lab test catalog entry                                                        107.28s  

   FAIL  Tests\Feature\Controllers\PermissionEnforcementTest
  ✓ Core permission pages → it forbids and allows users index based on users.view permission                       99.84s  
  ✓ Core permission pages → it forbids and allows dashboard based on dashboard.view permission                     94.30s  
  ✓ Core permission pages → it forbids and allows facility branch index based on facility_branches.view permissio… 92.41s  
  ✓ Core permission pages → it forbids and allows facility branch creation page based on facility_branches.creat… 118.93s  
  ✓ Tenant support and onboarding permissions → it forbids and allows support users opening the facility switche… 160.88s  
  ✓ Tenant support and onboarding permissions → it forbids and allows support users switching tenant context base… 95.21s  
  ✓ Tenant support and onboarding permissions → it forbids and allows onboarding access based on tenants.onboard… 112.79s  
  ✓ Visit workflow permissions → it forbids and allows visit status updates based on visits.update permission     108.89s  
  ✓ Visit workflow permissions → it forbids and allows triage creation based on triage.create permission          157.44s  
  ✓ Visit workflow permissions → it forbids and allows vital sign creation based on triage.update permission      155.22s  
  ✓ Consultation workflow permissions → it allows support users with consultation permission to open the consult… 130.91s  
  ✓ Consultation workflow permissions → it allows support users with consultation permission to open a consultat… 101.75s  
  ✓ Consultation workflow permissions → it forbids and allows consultation creation based on consultations.create… 83.49s  
  ✓ Consultation workflow permissions → it forbids and allows consultation updates based on consultations.update…  81.13s  
  ✓ Consultation workflow permissions → it forbids and allows lab request creation based on consultations.update…  64.09s  
  ✓ Consultation workflow permissions → it forbids and allows prescription creation based on consultations.update… 66.55s  
  ✓ Consultation workflow permissions → it forbids and allows facility service orders based on consultations.upda… 67.88s  
  ✓ Consultation workflow permissions → it forbids and allows pending facility service order removal based on con… 83.06s  
  ✓ Consultation workflow permissions → it blocks facility service deletion when service orders exist             107.64s  
  ✓ Consultation workflow permissions → it allows facility service deletion when no service orders exist           86.27s  
  ⨯ Appointment action permissions → it forbids and allows appointment confirmation based on appointments.confirm… 78.22s  

   PASS  Tests\Feature\Controllers\PurchaseOrderControllerTest
  ✓ it lists purchase orders for authorized user                                                                   78.74s  
  ✓ it denies purchase order index without permission                                                              62.59s  
  ✓ it creates a purchase order with items                                                                         68.14s  
  ✓ it submits a draft purchase order                                                                             104.48s  
  ✓ it approves a submitted purchase order                                                                         71.13s  
  ✓ it cancels a purchase order                                                                                    77.22s  
  ✓ it prevents submitting a non-draft purchase order                                                              93.07s  
  ✓ it prevents editing a non-draft purchase order                                                                129.53s  
  ✓ it shows a purchase order detail page                                                                         151.69s  

   FAIL  Tests\Feature\Controllers\SessionControllerTest
  ✓ it renders login page                                                                                           1.85s  
  ⨯ it may create a session                                                                                         2.07s  
  ⨯ it may create a session with remember me                                                                        2.07s  
  ✓ it redirects to two-factor challenge when enabled                                                               1.54s  
  ✓ it fails with invalid credentials                                                                               1.58s  
  ✓ it requires email                                                                                               1.41s  
  ✓ it requires password                                                                                            1.52s  
  ✓ it may destroy a session                                                                                        1.67s  
  ✓ it redirects authenticated users away from login                                                                1.73s  
  ✓ it throttles login attempts after too many failures                                                             2.25s  
  ⨯ it clears rate limit after successful login                                                                     2.01s  
  ✓ it dispatches lockout event when rate limit is reached                                                          2.65s  



osure))
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