 vendor/bin/phpstan analyse
Note: Using configuration file C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\phpstan.neon.
 760/760 [============================] 100%


  Line   app\Actions\RecordPharmacyPosPaymentAction.php  

  :32    Cannot cast mixed to float.                     
         🪪  cast.double                                 



  Line   app\Actions\RefundPharmacyPosSaleAction.php                                                                             

  :37    Instanceof between App\Models\PharmacyPosSaleItem and App\Models\PharmacyPosSaleItem will always evaluate to true.      
         🪪  instanceof.alwaysTrue                                                                                               
         💡  Because the type is coming from a PHPDoc, you can turn off this check by setting treatPhpDocTypesAsCertain: false i  
         n your phpstan.neon.                                                                                                    
  :42    Instanceof between App\Models\PharmacyPosSaleItemAllocation and App\Models\PharmacyPosSaleItemAllocation will always    
         evaluate to true.                                                                                                       
         🪪  instanceof.alwaysTrue                                                                                               
         💡  Because the type is coming from a PHPDoc, you can turn off this check by setting treatPhpDocTypesAsCertain: false i  
         n your phpstan.neon.                                                                                                    
  :66    Cannot cast mixed to float.                                                                                             
         🪪  cast.double                                                                                                         



  Line   app\Actions\RegisterPatientAndStartVisit.php                                                      

  :56    Using nullsafe property access "?->tenant_id" on left side of ?? is unnecessary. Use -> instead.  
         🪪  nullsafe.neverNull                                                                            



  Line   app\Actions\ResolveVisitChargeAmount.php  

  :53    Cannot cast mixed to float.               
         🪪  cast.double                           


 
  Line   app\Actions\StartTenantSubscription.php                                                                              
 
  :46    Only iterables can be unpacked, array|string given.                                                                  
         🪪  arrayUnpacking.nonIterable                                                                                       
  :51    Method App\Actions\StartTenantSubscription::markPendingActivation() should return App\Models\TenantSubscription but  
         returns App\Models\TenantSubscription|null.                                                                          
         🪪  return.type                                                                                                      
  :65    Only iterables can be unpacked, array|string given.                                                                  
         🪪  arrayUnpacking.nonIterable                                                                                       
  :71    Method App\Actions\StartTenantSubscription::markActive() should return App\Models\TenantSubscription but returns     
         App\Models\TenantSubscription|null.                                                                                  
         🪪  return.type                                                                                                      
  :80    Only iterables can be unpacked, array|string given.                                                                  
         🪪  arrayUnpacking.nonIterable                                                                                       
  :85    Method App\Actions\StartTenantSubscription::markFailed() should return App\Models\TenantSubscription but returns     
         App\Models\TenantSubscription|null.                                                                                  
         🪪  return.type                                                                                                      


  Line   app\Actions\SyncAppointmentStatusFromVisit.php          

  :25    Match expression does not handle remaining value: null  
         🪪  match.unhandled                                     



  Line   app\Actions\UpdateOnboardingProfile.php                                                                                 

  :25    Access to an undefined property App\Models\Address|Illuminate\Database\Eloquent\Collection<int, App\Models\Address>::$  
         country_id.                                                                                                             
         🪪  property.notFound                                                                                                   
         💡  Learn more: https://phpstan.org/blog/solving-phpstan-access-to-undefined-property                                   
  :25    Using nullsafe property access "?->country_id" on left side of ?? is unnecessary. Use -> instead.                       
         🪪  nullsafe.neverNull                                                                                                  
  :26    Access to an undefined property App\Models\Address|Illuminate\Database\Eloquent\Collection<int, App\Models\Address>::$  
         id.                                                                                                                     
         🪪  property.notFound                                                                                                   
         💡  Learn more: https://phpstan.org/blog/solving-phpstan-access-to-undefined-property                                   
  :31    Method App\Actions\UpdateOnboardingProfile::handle() should return App\Models\Tenant but returns                        
         App\Models\Tenant|null.                                                                                                 
         🪪  return.type                                                                                                         



  Line   app\Actions\UpdateStaff.php   

  :28    Cannot cast mixed to string.  
         🪪  cast.string               



  Line   app\Actions\VoidPharmacyPosSaleAction.php                                                                               

  :34    Instanceof between App\Models\PharmacyPosSaleItem and App\Models\PharmacyPosSaleItem will always evaluate to true.      
         🪪  instanceof.alwaysTrue                                                                                               
         💡  Because the type is coming from a PHPDoc, you can turn off this check by setting treatPhpDocTypesAsCertain: false i  
         n your phpstan.neon.                                                                                                    
  :39    Instanceof between App\Models\PharmacyPosSaleItemAllocation and App\Models\PharmacyPosSaleItemAllocation will always    
         evaluate to true.                                                                                                       
         🪪  instanceof.alwaysTrue                                                                                               
         💡  Because the type is coming from a PHPDoc, you can turn off this check by setting treatPhpDocTypesAsCertain: false i  
         n your phpstan.neon.                                                                                                    



  Line   app\Data\Clinical\CompleteConsultationDTO.php               

  :57    PHPDoc tag @param references unknown parameter: $validated  
         🪪  parameter.notFound                                      



  Line   app\Data\Clinical\CreateConsultationDTO.php                 

  :42    PHPDoc tag @param references unknown parameter: $validated  
         🪪  parameter.notFound                                      



  Line   app\Data\Clinical\CreateFacilityServiceOrderDTO.php         

  :18    PHPDoc tag @param references unknown parameter: $validated  
         🪪  parameter.notFound                                      



  Line   app\Data\Clinical\CreateImagingRequestDTO.php               

  :40    PHPDoc tag @param references unknown parameter: $validated  
         🪪  parameter.notFound                                      



  Line   app\Data\Clinical\CreateLabRequestDTO.php                   

  :32    PHPDoc tag @param references unknown parameter: $validated  
         🪪  parameter.notFound                                      



  Line   app\Data\Clinical\CreatePrescriptionDTO.php                 

  :42    PHPDoc tag @param references unknown parameter: $validated  
         🪪  parameter.notFound                                      



  Line   app\Data\Clinical\CreateTriageRecordDTO.php                 

  :50    PHPDoc tag @param references unknown parameter: $validated  
         🪪  parameter.notFound                                      



  Line   app\Data\Clinical\CreateVitalSignDTO.php                    

  :56    PHPDoc tag @param references unknown parameter: $validated  
         🪪  parameter.notFound                                      



  Line   app\Data\Clinical\UpdateConsultationDTO.php                 

  :50    PHPDoc tag @param references unknown parameter: $validated  
         🪪  parameter.notFound                                      



  Line   app\Data\Clinical\UpdateFacilityServiceOrderDTO.php         

  :18    PHPDoc tag @param references unknown parameter: $validated  
         🪪  parameter.notFound                                      



  Line   app\Data\Clinical\UpdateLabRequestDTO.php                   

  :32    PHPDoc tag @param references unknown parameter: $validated  
         🪪  parameter.notFound                                      



  Line   app\Data\Inventory\CreateGoodsReceiptDTO.php                                                                            

  :44    PHPDoc tag @param references unknown parameter: $validated                                                              
         🪪  parameter.notFound                                                                                                  
  :75    Parameter #1 $array (list<string>) of array_values is already a list, call has no effect.                               
         🪪  arrayValues.list                                                                                                    
         💡  Because the type is coming from a PHPDoc, you can turn off this check by setting treatPhpDocTypesAsCertain: false i  
         n your phpstan.neon.                                                                                                    



  Line   app\Data\Onboarding\CreateOnboardingDepartmentsDTO.php      

  :27    PHPDoc tag @param references unknown parameter: $validated  
         🪪  parameter.notFound                                      



  Line   app\Data\Onboarding\CreateOnboardingPrimaryBranchDTO.php    

  :36    PHPDoc tag @param references unknown parameter: $validated  
         🪪  parameter.notFound                                      



  Line   app\Data\Onboarding\CreateOnboardingStaffMemberDTO.php      

  :45    PHPDoc tag @param references unknown parameter: $validated  
         🪪  parameter.notFound                                      



  Line   app\Data\Patient\CreatePatientRegistrationDTO.php           

  :73    PHPDoc tag @param references unknown parameter: $validated  
         🪪  parameter.notFound                                      



  Line   app\Data\Patient\UpdatePatientDTO.php                       

  :58    PHPDoc tag @param references unknown parameter: $validated  
         🪪  parameter.notFound                                      



  Line   app\Data\Pharmacy\CreateDispensingRecordDTO.php             

  :36    PHPDoc tag @param references unknown parameter: $validated  
         🪪  parameter.notFound                                      



  Line   app\Data\Pharmacy\DispensePrescriptionDTO.php               

  :40    PHPDoc tag @param references unknown parameter: $validated  
         🪪  parameter.notFound                                      



  Line   app\Data\Pharmacy\PostDispenseDTO.php                       

  :29    PHPDoc tag @param references unknown parameter: $validated  
         🪪  parameter.notFound                                      



  Line   app\Http\Controllers\AdministrationController.php                                                                       

  :33    Parameter #1 $stored of static method App\Support\GeneralSettings\GeneralSettingsRegistry::resolveValues() expects      
         array<string, string|null>, array<mixed> given.                                                                         
         🪪  argument.type                                                                                                       
  :43    Strict comparison using !== between string and null will always evaluate to true.                                       
         🪪  notIdentical.alwaysTrue                                                                                             
         💡  Because the type is coming from a PHPDoc, you can turn off this check by setting treatPhpDocTypesAsCertain: false i  
         n your phpstan.neon.                                                                                                    
  :174   Using nullsafe property access "?->is_support" on left side of ?? is unnecessary. Use -> instead.                       
         🪪  nullsafe.neverNull                                                                                                  


 
  Line   app\Http\Controllers\AllergenController.php                                                                            
 
  :95    Method App\Http\Controllers\AllergenController::allergenOptions() return type has no value type specified in iterable  
         type array.                                                                                                            
         🪪  missingType.iterableValue                                                                                          
         💡  See: https://phpstan.org/blog/solving-phpstan-no-value-type-specified-in-iterable-type                             
 

 
  Line   app\Http\Controllers\AppointmentCategoryController.php                                                               
 
  :120   Method App\Http\Controllers\AppointmentCategoryController::formOptions() return type has no value type specified in  
         iterable type array.                                                                                                 
         🪪  missingType.iterableValue                                                                                        
         💡  See: https://phpstan.org/blog/solving-phpstan-no-value-type-specified-in-iterable-type                           
 


  Line   app\Http\Controllers\AppointmentController.php                                                                          

  :355   Method App\Http\Controllers\AppointmentController::formOptions() return type has no value type specified in iterable    
         type array.                                                                                                             
         🪪  missingType.iterableValue                                                                                           
         💡  See: https://phpstan.org/blog/solving-phpstan-no-value-type-specified-in-iterable-type                              
  :520   Method App\Http\Controllers\AppointmentController::statusOptions() return type has no value type specified in iterable  
         type array.                                                                                                             
         🪪  missingType.iterableValue                                                                                           
         💡  See: https://phpstan.org/blog/solving-phpstan-no-value-type-specified-in-iterable-type                              
  :530   Method App\Http\Controllers\AppointmentController::appointmentQuery() return type with generic class                    
         Illuminate\Database\Eloquent\Builder does not specify its types: TModel                                                 
         🪪  missingType.generics                                                                                                



  Line   app\Http\Controllers\BranchSwitcherController.php                                                                       

  :45    Call to function is_string() with App\Enums\GeneralStatus will always evaluate to false.                                
         🪪  function.impossibleType                                                                                             
         💡  Because the type is coming from a PHPDoc, you can turn off this check by setting treatPhpDocTypesAsCertain: false i  
         n your phpstan.neon.                                                                                                    



  Line   app\Http\Controllers\CurrencyExchangeRateController.php                               

  :81    Parameter #1 $boolean of function abort_unless expects bool, bool|null given.         
         🪪  argument.type                                                                     
  :83    Using nullsafe property access on non-nullable type App\Models\User. Use -> instead.  
         🪪  nullsafe.neverNull                                                                



  Line   app\Http\Controllers\DispensingHistoryController.php                                                                    

  :32    Parameter #1 $callback of method Illuminate\Pagination\AbstractPaginator<int,Illuminate\Database\Eloquent\Model>::thro  
         ugh() expects callable(Illuminate\Database\Eloquent\Model, int): array{id: string, dispense_number: string, status:     
         'cancelled'|'draft'|'posted', status_label: string, dispensed_at: string|null, visit_number: string|null,               
         patient_name: string|null, patient_number: string|null, inventory_location_name: string|null, dispensed_by:             
         string|null}, Closure(App\Models\DispensingRecord): array{id: string, dispense_number: string, status:                  
         'cancelled'|'draft'|'posted', status_label: string, dispensed_at: string|null, visit_number: string|null,               
         patient_name: string|null, patient_number: string|null, inventory_location_name: string|null, dispensed_by:             
         string|null} given.                                                                                                     
         🪪  argument.type                                                                                                       
  :35    Using nullsafe property access on non-nullable type App\Enums\DispensingRecordStatus. Use -> instead.                   
         🪪  nullsafe.neverNull                                                                                                  
  :36    Using nullsafe method call on non-nullable type App\Enums\DispensingRecordStatus. Use -> instead.                       
         🪪  nullsafe.neverNull                                                                                                  
  :37    Using nullsafe method call on non-nullable type Carbon\CarbonImmutable. Use -> instead.                                 
         🪪  nullsafe.neverNull                                                                                                  
  :73    Parameter #1 $stream of function fputcsv expects resource, resource|false given.                                        
         🪪  argument.type                                                                                                       
  :91    Parameter #1 $callback of method Illuminate\Database\Eloquent\Builder<Illuminate\Database\Eloquent\Model>::each() expe  
         cts callable(Illuminate\Database\Eloquent\Model, int): mixed, Closure(App\Models\DispensingRecord): void given.         
         🪪  argument.type                                                                                                       
  :99    Using nullsafe property access "?->email" on left side of ?? is unnecessary. Use -> instead.                            
         🪪  nullsafe.neverNull                                                                                                  
  :106   Parameter #1 $stream of function fputcsv expects resource, resource|false given.                                        
         🪪  argument.type                                                                                                       
  :108   Expression on left side of ?? is not nullable.                                                                          
         🪪  nullCoalesce.expr                                                                                                   
  :108   Using nullsafe method call on non-nullable type App\Enums\DispensingRecordStatus. Use -> instead.                       
         🪪  nullsafe.neverNull                                                                                                  
  :109   Expression on left side of ?? is not nullable.                                                                          
         🪪  nullCoalesce.expr                                                                                                   
  :109   Using nullsafe method call on non-nullable type Carbon\CarbonImmutable. Use -> instead.                                 
         🪪  nullsafe.neverNull                                                                                                  
  :110   Using nullsafe property access "?->visit_number" on left side of ?? is unnecessary. Use -> instead.                     
         🪪  nullsafe.neverNull                                                                                                  
  :112   Using nullsafe property access "?->patient_number" on left side of ?? is unnecessary. Use -> instead.                   
         🪪  nullsafe.neverNull                                                                                                  
  :113   Using nullsafe property access "?->name" on left side of ?? is unnecessary. Use -> instead.                             
         🪪  nullsafe.neverNull                                                                                                  
  :115   Using nullsafe property access "?->generic_name" on left side of ?? is unnecessary. Use -> instead.                     
         🪪  nullsafe.neverNull                                                                                                  
  :115   Using nullsafe property access "?->name" on left side of ?? is unnecessary. Use -> instead.                             
         🪪  nullsafe.neverNull                                                                                                  
  :119   Expression on left side of ?? is not nullable.                                                                          
         🪪  nullCoalesce.expr                                                                                                   
  :119   Using nullsafe method call on non-nullable type App\Enums\PrescriptionItemStatus. Use -> instead.                       
         🪪  nullsafe.neverNull                                                                                                  
  :126   Parameter #1 $stream of function fclose expects resource, resource|false given.                                         
         🪪  argument.type                                                                                                       
  :130   Method App\Http\Controllers\DispensingHistoryController::baseQuery() return type with generic class                     
         Illuminate\Database\Eloquent\Builder does not specify its types: TModel                                                 
         🪪  missingType.generics                                                                                                



  Line   app\Http\Controllers\DoctorConsultationController.php                                                                   

  :254   Using nullsafe method call on non-nullable type Illuminate\Database\Eloquent\Collection<int, App\Models\LabRequest>. U  
         se -> instead.                                                                                                          
         🪪  nullsafe.neverNull                                                                                                  
  :255   Using nullsafe method call on non-nullable type Illuminate\Database\Eloquent\Collection<int, App\Models\LabRequestItem  
         >. Use -> instead.                                                                                                      
         🪪  nullsafe.neverNull                                                                                                  


- 
  Line   app\Http\Controllers\DoctorScheduleController.php                                                               
- 
  :144   Method App\Http\Controllers\DoctorScheduleController::formOptions() return type has no value type specified in  
         iterable type array.                                                                                            
         🪪  missingType.iterableValue                                                                                   
         💡  See: https://phpstan.org/blog/solving-phpstan-no-value-type-specified-in-iterable-type                      
- 

- 
  Line   app\Http\Controllers\DoctorScheduleExceptionController.php                                                            
- 
  :151   Method App\Http\Controllers\DoctorScheduleExceptionController::formOptions() return type has no value type specified  
         in iterable type array.                                                                                               
         🪪  missingType.iterableValue                                                                                         
         💡  See: https://phpstan.org/blog/solving-phpstan-no-value-type-specified-in-iterable-type                            
- 


  Line   app\Http\Controllers\FacilityManagerController.php                                                                      

  :77    Parameter #1 $callback of method Illuminate\Support\Collection<int,Illuminate\Database\Eloquent\Model>::filter() expec  
         ts (callable(Illuminate\Database\Eloquent\Model, int): bool)|null, Closure(App\Models\Tenant): bool given.              
         🪪  argument.type                                                                                                       
  :78    Cannot access property $value on string.                                                                                
         🪪  property.nonObject                                                                                                  
  :80    Parameter #1 $callback of method Illuminate\Support\Collection<int,Illuminate\Database\Eloquent\Model>::sortBy() expec  
         ts array<array{string, string}|(callable(Illuminate\Database\Eloquent\Model, Illuminate\Database\Eloquent\Model): mixe  
         d)|(callable(Illuminate\Database\Eloquent\Model, int): mixed)|string>|(callable(Illuminate\Database\Eloquent\Model, in  
         t): mixed)|string, Closure(App\Models\Tenant): (lowercase-string&non-falsy-string) given.                               
         🪪  argument.type                                                                                                       
  :83    Cannot access property $value on string.                                                                                
         🪪  property.nonObject                                                                                                  
  :88    Parameter #1 $callback of method Illuminate\Database\Eloquent\Collection<int,Illuminate\Database\Eloquent\Model>::map(  
         ) expects callable(Illuminate\Database\Eloquent\Model, int): array<string, mixed>, Closure(App\Models\Tenant): array<s  
         tring, mixed> given.                                                                                                    
         🪪  argument.type                                                                                                       
  :144   Parameter #1 $callback of method Illuminate\Pagination\AbstractPaginator<int,Illuminate\Database\Eloquent\Model>::thro  
         ugh() expects callable(Illuminate\Database\Eloquent\Model, int): array<string, mixed>, Closure(App\Models\Tenant): arr  
         ay<string, mixed> given.                                                                                                
         🪪  argument.type                                                                                                       
  :225   Call to function is_string() with App\Enums\GeneralStatus will always evaluate to false.                                
         🪪  function.impossibleType                                                                                             
         💡  Because the type is coming from a PHPDoc, you can turn off this check by setting treatPhpDocTypesAsCertain: false i  
         n your phpstan.neon.                                                                                                    
  :225   Using nullsafe property access on non-nullable type App\Enums\GeneralStatus. Use -> instead.                            
         🪪  nullsafe.neverNull                                                                                                  
  :265   Using nullsafe property access on non-nullable type App\Enums\GeneralStatus. Use -> instead.                            
         🪪  nullsafe.neverNull                                                                                                  
  :284   Call to function is_string() with App\Enums\GeneralStatus will always evaluate to false.                                
         🪪  function.impossibleType                                                                                             
         💡  Because the type is coming from a PHPDoc, you can turn off this check by setting treatPhpDocTypesAsCertain: false i  
         n your phpstan.neon.                                                                                                    
  :284   Using nullsafe property access on non-nullable type App\Enums\GeneralStatus. Use -> instead.                            
         🪪  nullsafe.neverNull                                                                                                  
  :590   Method App\Http\Controllers\FacilityManagerController::filteredTenantQuery() return type with generic class             
         Illuminate\Database\Eloquent\Builder does not specify its types: TModel                                                 
         🪪  missingType.generics                                                                                                
  :599   Call to function is_string() with string will always evaluate to true.                                                  
         🪪  function.alreadyNarrowedType                                                                                        
         💡  Because the type is coming from a PHPDoc, you can turn off this check by setting treatPhpDocTypesAsCertain: false i  
         n your phpstan.neon.                                                                                                    
  :621   Call to function is_string() with string will always evaluate to true.                                                  
         🪪  function.alreadyNarrowedType                                                                                        
         💡  Because the type is coming from a PHPDoc, you can turn off this check by setting treatPhpDocTypesAsCertain: false i  
         n your phpstan.neon.                                                                                                    
  :629   Method App\Http\Controllers\FacilityManagerController::tenantsWithCounts() has parameter $query with generic class      
         Illuminate\Database\Eloquent\Builder but does not specify its types: TModel                                             
         🪪  missingType.generics                                                                                                
  :629   Method App\Http\Controllers\FacilityManagerController::tenantsWithCounts() return type with generic class               
         Illuminate\Database\Eloquent\Builder does not specify its types: TModel                                                 
         🪪  missingType.generics                                                                                                
  :656   Using nullsafe property access on non-nullable type App\Enums\GeneralStatus. Use -> instead.                            
         🪪  nullsafe.neverNull                                                                                                  
  :657   Using nullsafe property access on non-nullable type App\Enums\FacilityLevel. Use -> instead.                            
         🪪  nullsafe.neverNull                                                                                                  
  :660   Cannot access property $value on string.                                                                                
         🪪  property.nonObject                                                                                                  
  :661   Cannot call method label() on string.                                                                                   
         🪪  method.nonObject                                                                                                    
  :693   Using nullsafe property access on non-nullable type App\Enums\GeneralStatus. Use -> instead.                            
         🪪  nullsafe.neverNull                                                                                                  
  :694   Using nullsafe property access on non-nullable type App\Enums\FacilityLevel. Use -> instead.                            
         🪪  nullsafe.neverNull                                                                                                  
  :719   Cannot access property $value on string.                                                                                
         🪪  property.nonObject                                                                                                  
  :720   Cannot call method label() on string.                                                                                   
         🪪  method.nonObject                                                                                                    
  :725   Cannot call method toISOString() on string.                                                                             
         🪪  method.nonObject                                                                                                    
  :726   Cannot call method toISOString() on string.                                                                             
         🪪  method.nonObject                                                                                                    
  :727   Cannot call method toISOString() on string.                                                                             
         🪪  method.nonObject                                                                                                    
  :744   Using nullsafe method call on non-nullable type Carbon\CarbonInterface. Use -> instead.                                 
         🪪  nullsafe.neverNull                                                                                                  
  :745   Using nullsafe property access "?->is_active" on left side of ?? is unnecessary. Use -> instead.                        
         🪪  nullsafe.neverNull                                                                                                  
  :753   Cannot access property $is_primary_location on mixed.                                                                   
         🪪  property.nonObject                                                                                                  
  :753   Using nullsafe property access "?->is_primary_location" on left side of ?? is unnecessary. Use -> instead.              
         🪪  nullsafe.neverNull                                                                                                  
  :809   Using nullsafe method call on non-nullable type Carbon\CarbonImmutable. Use -> instead.                                 
         🪪  nullsafe.neverNull                                                                                                  
  :838   Using nullsafe method call on non-nullable type Illuminate\Support\Carbon. Use -> instead.                              
         🪪  nullsafe.neverNull                                                                                                  
  :854   Method App\Http\Controllers\FacilityManagerController::recentActivityForTenant() should return                          
         Illuminate\Support\Collection<int, array<string, string|null>> but returns Illuminate\Support\Collection<int, array{ty  
         pe: string, title: string, subject: string|null, timestamp: string}>.                                                   
         🪪  return.type                                                                                                         
         💡  Template type TValue on class Illuminate\Support\Collection is not covariant. Learn more: https://phpstan.org/blog/  
         whats-up-with-template-covariant                                                                                        


 
  Line   app\Http\Controllers\FacilityServiceController.php                                                               
 
  :93    Method App\Http\Controllers\FacilityServiceController::formOptions() return type has no value type specified in  
         iterable type array.                                                                                             
         🪪  missingType.iterableValue                                                                                    
         💡  See: https://phpstan.org/blog/solving-phpstan-no-value-type-specified-in-iterable-type                       
 


  Line   app\Http\Controllers\InventoryDashboardController.php                                                                   

  :58    Access to an undefined property App\Models\StockMovement::$total_qty.                                                   
         🪪  property.notFound                                                                                                   
         💡  Learn more: https://phpstan.org/blog/solving-phpstan-access-to-undefined-property                                   
  :58    Cannot cast mixed to float.                                                                                             
         🪪  cast.double                                                                                                         
  :63    Using nullsafe property access "?->minimum_stock_level" on left side of ?? is unnecessary. Use -> instead.              
         🪪  nullsafe.neverNull                                                                                                  
  :64    Access to an undefined property App\Models\StockMovement::$total_qty.                                                   
         🪪  property.notFound                                                                                                   
         💡  Learn more: https://phpstan.org/blog/solving-phpstan-access-to-undefined-property                                   
  :64    Cannot cast mixed to float.                                                                                             
         🪪  cast.double                                                                                                         
  :71    Access to an undefined property App\Models\StockMovement::$total_qty.                                                   
         🪪  property.notFound                                                                                                   
         💡  Learn more: https://phpstan.org/blog/solving-phpstan-access-to-undefined-property                                   
  :71    Cannot cast mixed to float.                                                                                             
         🪪  cast.double                                                                                                         
  :72    Using nullsafe property access "?->default_purchase_price" on left side of ?? is unnecessary. Use -> instead.           
         🪪  nullsafe.neverNull                                                                                                  
  :89    Access to an undefined property App\Models\InventoryItem::$count.                                                       
         🪪  property.notFound                                                                                                   
         💡  Learn more: https://phpstan.org/blog/solving-phpstan-access-to-undefined-property                                   
  :89    Unable to resolve the template type TMapWithKeysValue in call to method Illuminate\Database\Eloquent\Collection<int,Ap  
         p\Models\InventoryItem>::mapWithKeys()                                                                                  
         🪪  argument.templateType                                                                                               
         💡  See: https://phpstan.org/blog/solving-phpstan-error-unable-to-resolve-template-type                                 
  :98    Access to an undefined property App\Models\InventoryItem::$count.                                                       
         🪪  property.notFound                                                                                                   
         💡  Learn more: https://phpstan.org/blog/solving-phpstan-access-to-undefined-property                                   
  :98    Cannot access property $value on App\Enums\DrugCategory|null.                                                           
         🪪  property.nonObject                                                                                                  
  :98    Unable to resolve the template type TMapWithKeysValue in call to method Illuminate\Database\Eloquent\Collection<int,Ap  
         p\Models\InventoryItem>::mapWithKeys()                                                                                  
         🪪  argument.templateType                                                                                               
         💡  See: https://phpstan.org/blog/solving-phpstan-error-unable-to-resolve-template-type                                 
  :111   Access to an undefined property App\Models\PurchaseOrder::$count.                                                       
         🪪  property.notFound                                                                                                   
         💡  Learn more: https://phpstan.org/blog/solving-phpstan-access-to-undefined-property                                   
  :111   Unable to resolve the template type TMapWithKeysValue in call to method Illuminate\Database\Eloquent\Collection<int,Ap  
         p\Models\PurchaseOrder>::mapWithKeys()                                                                                  
         🪪  argument.templateType                                                                                               
         💡  See: https://phpstan.org/blog/solving-phpstan-error-unable-to-resolve-template-type                                 


 
  Line   app\Http\Controllers\InventoryMovementReportController.php                                           
 
  :91    Using nullsafe property access "?->generic_name" on left side of ?? is unnecessary. Use -> instead.  
         🪪  nullsafe.neverNull                                                                               
  :94    Using nullsafe property access on non-nullable type App\Enums\StockMovementType. Use -> instead.     
         🪪  nullsafe.neverNull                                                                               
  :95    Using nullsafe method call on non-nullable type App\Enums\StockMovementType. Use -> instead.         
         🪪  nullsafe.neverNull                                                                               
  :100   Using nullsafe method call on non-nullable type Carbon\CarbonImmutable. Use -> instead.              
         🪪  nullsafe.neverNull                                                                               
 


  Line   app\Http\Controllers\StaffController.php                                                                                

  :173   Parameter #1 $array of function array_unique expects an array of values castable to string, array<mixed, mixed> given.  
         🪪  argument.type                                                                                                       



  Line   app\Http\Requests\CorrectLabResultEntryRequest.php                                                                      

  :65    Strict comparison using !== between App\Models\LabTestCatalog and null will always evaluate to true.                    
         🪪  notIdentical.alwaysTrue                                                                                             
         💡  Because the type is coming from a PHPDoc, you can turn off this check by setting treatPhpDocTypesAsCertain: false i  
         n your phpstan.neon.                                                                                                    
  :94    Strict comparison using !== between App\Models\LabTestCatalog and null will always evaluate to true.                    
         🪪  notIdentical.alwaysTrue                                                                                             
         💡  Because the type is coming from a PHPDoc, you can turn off this check by setting treatPhpDocTypesAsCertain: false i  
         n your phpstan.neon.                                                                                                    



  Line   app\Http\Requests\StoreConsultationFacilityServiceOrderRequest.php                                                      

  :20    Method App\Http\Requests\StoreConsultationFacilityServiceOrderRequest::rules() return type has no value type specified  
         in iterable type array.                                                                                                 
         🪪  missingType.iterableValue                                                                                           
         💡  See: https://phpstan.org/blog/solving-phpstan-no-value-type-specified-in-iterable-type                              


 
  Line   app\Http\Requests\StoreConsultationImagingRequest.php                                                                  
 
  :22    Method App\Http\Requests\StoreConsultationImagingRequest::rules() return type has no value type specified in iterable  
         type array.                                                                                                            
         🪪  missingType.iterableValue                                                                                          
         💡  See: https://phpstan.org/blog/solving-phpstan-no-value-type-specified-in-iterable-type                             
 


  Line   app\Http\Requests\StoreConsultationLabRequest.php                                                                       

  :20    Method App\Http\Requests\StoreConsultationLabRequest::rules() return type has no value type specified in iterable type  
         array.                                                                                                                  
         🪪  missingType.iterableValue                                                                                           
         💡  See: https://phpstan.org/blog/solving-phpstan-no-value-type-specified-in-iterable-type                              


- 
  Line   app\Http\Requests\StoreConsultationPrescriptionRequest.php                                                            
- 
  :58    Offset 'is_prn' on array{inventory_item_id: string, dosage: string, frequency: string, route: string, duration_days:  
         int, quantity: int, instructions: string|null, is_prn: bool, ...} on left side of ?? always exists and is not         
         nullable.                                                                                                             
         🪪  nullCoalesce.offset                                                                                               
- 

 
  Line   app\Http\Requests\UpdateAppointmentCategoryRequest.php                                               
 
  :27    Using nullsafe property access on non-nullable type App\Models\AppointmentCategory. Use -> instead.  
         🪪  nullsafe.neverNull                                                                               
 


  Line   app\Http\Requests\UpdateAppointmentModeRequest.php                                               

  :27    Using nullsafe property access on non-nullable type App\Models\AppointmentMode. Use -> instead.  
         🪪  nullsafe.neverNull                                                                           
                                                                                                                    
 [ERROR] Found 140 errors                                                                                          