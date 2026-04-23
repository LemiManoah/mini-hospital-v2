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



  Line   app\Http\Controllers\DoctorScheduleController.php                                                               

  :144   Method App\Http\Controllers\DoctorScheduleController::formOptions() return type has no value type specified in  
         iterable type array.                                                                                            
         🪪  missingType.iterableValue                                                                                   
         💡  See: https://phpstan.org/blog/solving-phpstan-no-value-type-specified-in-iterable-type                      



  Line   app\Http\Controllers\DoctorScheduleExceptionController.php                                                            

  :151   Method App\Http\Controllers\DoctorScheduleExceptionController::formOptions() return type has no value type specified  
         in iterable type array.                                                                                               
         🪪  missingType.iterableValue                                                                                         
         💡  See: https://phpstan.org/blog/solving-phpstan-no-value-type-specified-in-iterable-type                            



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



  Line   app\Http\Controllers\LaboratoryDashboardController.php                                                                  

  :104   Ternary operator condition is always true.                                                                              
         🪪  ternary.alwaysTrue                                                                                                  
         💡  Because the type is coming from a PHPDoc, you can turn off this check by setting treatPhpDocTypesAsCertain: false i  
         n your phpstan.neon.                                                                                                    
  :111   Cannot cast mixed to float.                                                                                             
         🪪  cast.double                                                                                                         
  :216   Parameter #1 $relations of method Illuminate\Database\Eloquent\Builder<App\Models\LabRequest>::with() expects array<ar  
         ray|(Closure(Illuminate\Database\Eloquent\Relations\Relation<*, *, *>): mixed)|string>|string, array{0:                 
         'requestedBy:id…', 1: 'visit:id,visit…', 2: 'visit.patient:id…', items:                                                 
         Closure(Illuminate\Database\Eloquent\Relations\HasMany): void} given.                                                   
         🪪  argument.type                                                                                                       



  Line   app\Http\Controllers\LaboratoryQueueController.php                                                                      

  :77    Parameter #1 $query of method App\Http\Controllers\LaboratoryQueueController::applyStageFilter() expects                
         Illuminate\Database\Eloquent\Builder<App\Models\LabRequestItem>|Illuminate\Database\Eloquent\Relations\HasMany<App\Mod  
         els\LabRequestItem, App\Models\LabRequest>, Illuminate\Database\Eloquent\Builder<Illuminate\Database\Eloquent\Model> g  
         iven.                                                                                                                   
         🪪  argument.type                                                                                                       
  :79    Parameter #1 $relations of method Illuminate\Database\Eloquent\Builder<App\Models\LabRequest>::with() expects array<ar  
         ray|(Closure(Illuminate\Database\Eloquent\Relations\Relation<*, *, *>): mixed)|string>|string, array{0:                 
         'requestedBy:id…', 1: 'visit:id,visit…', 2: 'visit.patient:id…', items:                                                 
         Closure(Illuminate\Database\Eloquent\Relations\HasMany): void} given.                                                   
         🪪  argument.type                                                                                                       



  Line   app\Http\Controllers\PatientController.php                                                                              

  :123   Parameter #1 $cases of method App\Http\Controllers\PatientController::enumOptions() expects array<int, object{value: s  
         tring, label: string}>, array<int, App\Enums\Gender::FEMALE|App\Enums\Gender::MALE> given.                              
         🪪  argument.type                                                                                                       
         💡  App\Enums\Gender::MALE does not have property $label.                                                               
         💡  App\Enums\Gender::FEMALE does not have property $label.                                                             
  :124   Parameter #1 $cases of method App\Http\Controllers\PatientController::enumOptions() expects array<int, object{value: s  
         tring, label: string}>, array<int, App\Enums\MaritalStatus::DIVORCED|App\Enums\MaritalStatus::MARRIED|App\Enums\Marita  
         lStatus::SEPARATED|App\Enums\MaritalStatus::SINGLE|App\Enums\MaritalStatus::WIDOWED> given.                             
         🪪  argument.type                                                                                                       
         💡  App\Enums\MaritalStatus::SINGLE does not have property $label.                                                      
         💡  App\Enums\MaritalStatus::MARRIED does not have property $label.                                                     
         💡  App\Enums\MaritalStatus::DIVORCED does not have property $label.                                                    
         💡  App\Enums\MaritalStatus::WIDOWED does not have property $label.                                                     
         💡  App\Enums\MaritalStatus::SEPARATED does not have property $label.                                                   
  :125   Parameter #1 $cases of method App\Http\Controllers\PatientController::enumOptions() expects array<int, object{value: s  
         tring, label: string}>, array<int, App\Enums\BloodGroup::A_NEGATIVE|App\Enums\BloodGroup::A_POSITIVE|App\Enums\BloodGr  
         oup::AB_NEGATIVE|App\Enums\BloodGroup::AB_POSITIVE|App\Enums\BloodGroup::B_NEGATIVE|App\Enums\BloodGroup::B_POSITIVE|A  
         pp\Enums\BloodGroup::O_NEGATIVE|App\Enums\BloodGroup::O_POSITIVE|App\Enums\BloodGroup::UNKNOWN> given.                  
         🪪  argument.type                                                                                                       
         💡  App\Enums\BloodGroup::A_POSITIVE does not have property $label.                                                     
         💡  App\Enums\BloodGroup::A_NEGATIVE does not have property $label.                                                     
         💡  App\Enums\BloodGroup::B_POSITIVE does not have property $label.                                                     
         💡  App\Enums\BloodGroup::B_NEGATIVE does not have property $label.                                                     
         💡  App\Enums\BloodGroup::AB_POSITIVE does not have property $label.                                                    
         💡  App\Enums\BloodGroup::AB_NEGATIVE does not have property $label.                                                    
         💡  App\Enums\BloodGroup::O_POSITIVE does not have property $label.                                                     
         💡  App\Enums\BloodGroup::O_NEGATIVE does not have property $label.                                                     
         💡  App\Enums\BloodGroup::UNKNOWN does not have property $label.                                                        
  :126   Parameter #1 $cases of method App\Http\Controllers\PatientController::enumOptions() expects array<int, object{value: s  
         tring, label: string}>, array<int, App\Enums\Religion::BUDDHIST|App\Enums\Religion::CHRISTIAN|App\Enums\Religion::HIND  
         U|App\Enums\Religion::MUSLIM|App\Enums\Religion::OTHER|App\Enums\Religion::UNKNOWN> given.                              
         🪪  argument.type                                                                                                       
         💡  App\Enums\Religion::CHRISTIAN does not have property $label.                                                        
         💡  App\Enums\Religion::MUSLIM does not have property $label.                                                           
         💡  App\Enums\Religion::HINDU does not have property $label.                                                            
         💡  App\Enums\Religion::BUDDHIST does not have property $label.                                                         
         💡  App\Enums\Religion::OTHER does not have property $label.                                                            
         💡  App\Enums\Religion::UNKNOWN does not have property $label.                                                          
  :127   Parameter #1 $cases of method App\Http\Controllers\PatientController::enumOptions() expects array<int, object{value: s  
         tring, label: string}>, array<int, App\Enums\KinRelationship::CHILD|App\Enums\KinRelationship::OTHER|App\Enums\KinRela  
         tionship::PARENT|App\Enums\KinRelationship::SIBLING|App\Enums\KinRelationship::SPOUSE|App\Enums\KinRelationship::UNKNO  
         WN> given.                                                                                                              
         🪪  argument.type                                                                                                       
         💡  App\Enums\KinRelationship::SPOUSE does not have property $label.                                                    
         💡  App\Enums\KinRelationship::PARENT does not have property $label.                                                    
         💡  App\Enums\KinRelationship::CHILD does not have property $label.                                                     
         💡  App\Enums\KinRelationship::SIBLING does not have property $label.                                                   
         💡  App\Enums\KinRelationship::OTHER does not have property $label.                                                     
         💡  App\Enums\KinRelationship::UNKNOWN does not have property $label.                                                   
  :161   Parameter #1 $cases of method App\Http\Controllers\PatientController::enumOptions() expects array<int, object{value: s  
         tring, label: string}>, array<int, App\Enums\Gender::FEMALE|App\Enums\Gender::MALE> given.                              
         🪪  argument.type                                                                                                       
         💡  App\Enums\Gender::MALE does not have property $label.                                                               
         💡  App\Enums\Gender::FEMALE does not have property $label.                                                             
  :162   Parameter #1 $cases of method App\Http\Controllers\PatientController::enumOptions() expects array<int, object{value: s  
         tring, label: string}>, array<int, App\Enums\MaritalStatus::DIVORCED|App\Enums\MaritalStatus::MARRIED|App\Enums\Marita  
         lStatus::SEPARATED|App\Enums\MaritalStatus::SINGLE|App\Enums\MaritalStatus::WIDOWED> given.                             
         🪪  argument.type                                                                                                       
         💡  App\Enums\MaritalStatus::SINGLE does not have property $label.                                                      
         💡  App\Enums\MaritalStatus::MARRIED does not have property $label.                                                     
         💡  App\Enums\MaritalStatus::DIVORCED does not have property $label.                                                    
         💡  App\Enums\MaritalStatus::WIDOWED does not have property $label.                                                     
         💡  App\Enums\MaritalStatus::SEPARATED does not have property $label.                                                   
  :163   Parameter #1 $cases of method App\Http\Controllers\PatientController::enumOptions() expects array<int, object{value: s  
         tring, label: string}>, array<int, App\Enums\BloodGroup::A_NEGATIVE|App\Enums\BloodGroup::A_POSITIVE|App\Enums\BloodGr  
         oup::AB_NEGATIVE|App\Enums\BloodGroup::AB_POSITIVE|App\Enums\BloodGroup::B_NEGATIVE|App\Enums\BloodGroup::B_POSITIVE|A  
         pp\Enums\BloodGroup::O_NEGATIVE|App\Enums\BloodGroup::O_POSITIVE|App\Enums\BloodGroup::UNKNOWN> given.                  
         🪪  argument.type                                                                                                       
         💡  App\Enums\BloodGroup::A_POSITIVE does not have property $label.                                                     
         💡  App\Enums\BloodGroup::A_NEGATIVE does not have property $label.                                                     
         💡  App\Enums\BloodGroup::B_POSITIVE does not have property $label.                                                     
         💡  App\Enums\BloodGroup::B_NEGATIVE does not have property $label.                                                     
         💡  App\Enums\BloodGroup::AB_POSITIVE does not have property $label.                                                    
         💡  App\Enums\BloodGroup::AB_NEGATIVE does not have property $label.                                                    
         💡  App\Enums\BloodGroup::O_POSITIVE does not have property $label.                                                     
         💡  App\Enums\BloodGroup::O_NEGATIVE does not have property $label.                                                     
         💡  App\Enums\BloodGroup::UNKNOWN does not have property $label.                                                        
  :164   Parameter #1 $cases of method App\Http\Controllers\PatientController::enumOptions() expects array<int, object{value: s  
         tring, label: string}>, array<int, App\Enums\Religion::BUDDHIST|App\Enums\Religion::CHRISTIAN|App\Enums\Religion::HIND  
         U|App\Enums\Religion::MUSLIM|App\Enums\Religion::OTHER|App\Enums\Religion::UNKNOWN> given.                              
         🪪  argument.type                                                                                                       
         💡  App\Enums\Religion::CHRISTIAN does not have property $label.                                                        
         💡  App\Enums\Religion::MUSLIM does not have property $label.                                                           
         💡  App\Enums\Religion::HINDU does not have property $label.                                                            
         💡  App\Enums\Religion::BUDDHIST does not have property $label.                                                         
         💡  App\Enums\Religion::OTHER does not have property $label.                                                            
         💡  App\Enums\Religion::UNKNOWN does not have property $label.                                                          
  :165   Parameter #1 $cases of method App\Http\Controllers\PatientController::enumOptions() expects array<int, object{value: s  
         tring, label: string}>, array<int, App\Enums\KinRelationship::CHILD|App\Enums\KinRelationship::OTHER|App\Enums\KinRela  
         tionship::PARENT|App\Enums\KinRelationship::SIBLING|App\Enums\KinRelationship::SPOUSE|App\Enums\KinRelationship::UNKNO  
         WN> given.                                                                                                              
         🪪  argument.type                                                                                                       
         💡  App\Enums\KinRelationship::SPOUSE does not have property $label.                                                    
         💡  App\Enums\KinRelationship::PARENT does not have property $label.                                                    
         💡  App\Enums\KinRelationship::CHILD does not have property $label.                                                     
         💡  App\Enums\KinRelationship::SIBLING does not have property $label.                                                   
         💡  App\Enums\KinRelationship::OTHER does not have property $label.                                                     
         💡  App\Enums\KinRelationship::UNKNOWN does not have property $label.                                                   
  :240   Call to an undefined method object{value: string, label: string}::label().                                              
         🪪  method.notFound                                                                                                     
  :244   Method App\Http\Controllers\PatientController::enumOptions() should return array<int, array{value: string, label: stri  
         ng}> but returns list<array{value: string, label: mixed}>.                                                              
         🪪  return.type                                                                                                         
         💡  Offset 'label' (string) does not accept type mixed.                                                                 



  Line   app\Http\Controllers\PatientVisitController.php                                                                         

  :205   Parameter #1 $cases of method App\Http\Controllers\PatientVisitController::enumOptions() expects array<int, object{val  
         ue: string, label: string}>, array<int, App\Enums\TriageGrade::BLACK|App\Enums\TriageGrade::GREEN|App\Enums\TriageGrad  
         e::RED|App\Enums\TriageGrade::YELLOW> given.                                                                            
         🪪  argument.type                                                                                                       
         💡  App\Enums\TriageGrade::RED does not have property $label.                                                           
         💡  App\Enums\TriageGrade::YELLOW does not have property $label.                                                        
         💡  App\Enums\TriageGrade::GREEN does not have property $label.                                                         
         💡  App\Enums\TriageGrade::BLACK does not have property $label.                                                         
  :206   Parameter #1 $cases of method App\Http\Controllers\PatientVisitController::enumOptions() expects array<int, object{val  
         ue: string, label: string}>, array<int, App\Enums\AttendanceType::NEW|App\Enums\AttendanceType::RE_ATTENDANCE|App\Enum  
         s\AttendanceType::REFERRAL> given.                                                                                      
         🪪  argument.type                                                                                                       
         💡  App\Enums\AttendanceType::NEW does not have property $label.                                                        
         💡  App\Enums\AttendanceType::RE_ATTENDANCE does not have property $label.                                              
         💡  App\Enums\AttendanceType::REFERRAL does not have property $label.                                                   
  :207   Parameter #1 $cases of method App\Http\Controllers\PatientVisitController::enumOptions() expects array<int, object{val  
         ue: string, label: string}>, array<int, App\Enums\ConsciousLevel::ALERT|App\Enums\ConsciousLevel::PAIN|App\Enums\Consc  
         iousLevel::UNRESPONSIVE|App\Enums\ConsciousLevel::VOICE> given.                                                         
         🪪  argument.type                                                                                                       
         💡  App\Enums\ConsciousLevel::ALERT does not have property $label.                                                      
         💡  App\Enums\ConsciousLevel::VOICE does not have property $label.                                                      
         💡  App\Enums\ConsciousLevel::PAIN does not have property $label.                                                       
         💡  App\Enums\ConsciousLevel::UNRESPONSIVE does not have property $label.                                               
  :208   Parameter #1 $cases of method App\Http\Controllers\PatientVisitController::enumOptions() expects array<int, object{val  
         ue: string, label: string}>, array<int, App\Enums\MobilityStatus::ASSISTED|App\Enums\MobilityStatus::INDEPENDENT|App\E  
         nums\MobilityStatus::STRETCHER|App\Enums\MobilityStatus::WHEELCHAIR> given.                                             
         🪪  argument.type                                                                                                       
         💡  App\Enums\MobilityStatus::INDEPENDENT does not have property $label.                                                
         💡  App\Enums\MobilityStatus::ASSISTED does not have property $label.                                                   
         💡  App\Enums\MobilityStatus::WHEELCHAIR does not have property $label.                                                 
         💡  App\Enums\MobilityStatus::STRETCHER does not have property $label.                                                  
  :267   Cannot access offset 'clinic_id' on mixed.                                                                              
         🪪  offsetAccess.nonOffsetAccessible                                                                                    
  :268   Strict comparison using !== between mixed and null will always evaluate to true.                                        
         🪪  notIdentical.alwaysTrue                                                                                             
         💡  Type null has already been eliminated from mixed.                                                                   
  :278   Cannot access offset 'doctor_id' on mixed.                                                                              
         🪪  offsetAccess.nonOffsetAccessible                                                                                    
  :300   Cannot access offset 'visit_type' on mixed.                                                                             
         🪪  offsetAccess.nonOffsetAccessible                                                                                    
  :302   Cannot access offset 'clinic_id' on mixed.                                                                              
         🪪  offsetAccess.nonOffsetAccessible                                                                                    
  :303   Cannot access offset 'doctor_id' on mixed.                                                                              
         🪪  offsetAccess.nonOffsetAccessible                                                                                    
  :304   Cannot access offset 'is_emergency' on mixed.                                                                           
         🪪  offsetAccess.nonOffsetAccessible                                                                                    
  :314   Cannot access offset 'billing_type' on mixed.                                                                           
         🪪  offsetAccess.nonOffsetAccessible                                                                                    
  :315   Cannot access offset 'billing_type' on mixed.                                                                           
         🪪  offsetAccess.nonOffsetAccessible                                                                                    
  :316   Cannot access offset 'insurance_company_id' on mixed.                                                                   
         🪪  offsetAccess.nonOffsetAccessible                                                                                    
  :319   Cannot access offset 'insurance_package_id' on mixed.                                                                   
         🪪  offsetAccess.nonOffsetAccessible                                                                                    
  :339   Cannot access offset 'redirect_to' on mixed.                                                                            
         🪪  offsetAccess.nonOffsetAccessible                                                                                    
  :361   Cannot access offset 'redirect_to' on mixed.                                                                            
         🪪  offsetAccess.nonOffsetAccessible                                                                                    
  :367   Cannot access offset 'status' on mixed.                                                                                 
         🪪  offsetAccess.nonOffsetAccessible                                                                                    
  :368   Parameter #2 $redirectTo of method App\Http\Controllers\PatientVisitController::statusRedirect() expects string, mixed  
         given.                                                                                                                  
         🪪  argument.type                                                                                                       
  :378   Parameter #2 $redirectTo of method App\Http\Controllers\PatientVisitController::statusRedirect() expects string, mixed  
         given.                                                                                                                  
         🪪  argument.type                                                                                                       
  :382   Parameter #1 $value of static method App\Enums\VisitStatus::from() expects int|string, mixed given.                     
         🪪  argument.type                                                                                                       
  :384   Parameter #2 $redirectTo of method App\Http\Controllers\PatientVisitController::statusRedirect() expects string, mixed  
         given.                                                                                                                  
         🪪  argument.type                                                                                                       
  :411   Call to an undefined method object{value: string, label: string}::label().                                              
         🪪  method.notFound                                                                                                     
  :415   Method App\Http\Controllers\PatientVisitController::enumOptions() should return array<int, array{value: string, label:  
          string}> but returns list<array{value: string, label: mixed}>.                                                         
         🪪  return.type                                                                                                         
         💡  Offset 'label' (string) does not accept type mixed.                                                                 
  :427   Using nullsafe method call on non-nullable type Illuminate\Database\Eloquent\Collection<int, App\Models\LabRequest>. U  
         se -> instead.                                                                                                          
         🪪  nullsafe.neverNull                                                                                                  
  :428   Using nullsafe method call on non-nullable type Illuminate\Database\Eloquent\Collection<int, App\Models\LabRequestItem  
         >. Use -> instead.                                                                                                      
         🪪  nullsafe.neverNull                                                                                                  



  Line   app\Http\Controllers\PharmacyPosCartController.php                                                                    

  :37    Parameter #2 $attributes of method App\Actions\AddItemToPharmacyPosCartAction::handle() expects                       
         array{inventory_item_id?: string, quantity?: float|int|string, unit_price?: float|int|string|null, discount_amount?:  
         float|int|string|null, notes?: string|null}, array<string, mixed> given.                                              
         🪪  argument.type                                                                                                     
  :52    Parameter #2 $attributes of method App\Actions\UpdatePharmacyPosCartItemAction::handle() expects array{quantity?:     
         float|int|string, unit_price?: float|int|string|null, discount_amount?: float|int|string|null, notes?: string|null},  
         array<string, mixed> given.                                                                                           
         🪪  argument.type                                                                                                     



  Line   app\Http\Controllers\PharmacyQueueController.php                                                                        

  :57    Call to an undefined method Illuminate\Contracts\Pagination\LengthAwarePaginator<int, App\Models\Prescription>::throug  
         h().                                                                                                                    
         🪪  method.notFound                                                                                                     
  :125   Method App\Http\Controllers\PharmacyQueueController::itemBalancesForLocations() should return                           
         Illuminate\Support\Collection<string, float> but returns Illuminate\Support\Collection<(int|string), mixed>.            
         🪪  return.type                                                                                                         
  :132   Cannot cast mixed to float.                                                                                             
         🪪  cast.double                                                                                                         
  :147   Parameter #1 $items of method App\Http\Controllers\PharmacyQueueController::resolveAvailabilitySummary() expects        
         Illuminate\Support\Collection<int, array<string, mixed>>, Illuminate\Support\Collection<int, array<string, mixed>> giv  
         en.                                                                                                                     
         🪪  argument.type                                                                                                       
         💡  Template type TValue on class Illuminate\Support\Collection is not covariant. Learn more: https://phpstan.org/blog/  
         whats-up-with-template-covariant                                                                                        
  :188   Method App\Http\Controllers\PharmacyQueueController::serializeItem() has parameter $progress with no value type         
         specified in iterable type array.                                                                                       
         🪪  missingType.iterableValue                                                                                           
         💡  See: https://phpstan.org/blog/solving-phpstan-no-value-type-specified-in-iterable-type                              
  :190   Cannot access property $quantity on mixed.                                                                              
         🪪  property.nonObject                                                                                                  
  :190   Cannot cast mixed to float.                                                                                             
         🪪  cast.double                                                                                                         
  :191   Cannot cast mixed to float.                                                                                             
         🪪  cast.double                                                                                                         
  :192   Cannot cast mixed to float.                                                                                             
         🪪  cast.double                                                                                                         
  :194   Cannot access property $inventory_item_id on mixed.                                                                     
         🪪  property.nonObject                                                                                                  
  :194   Cannot cast mixed to string.                                                                                            
         🪪  cast.string                                                                                                         
  :203   Cannot access property $id on mixed.                                                                                    
         🪪  property.nonObject                                                                                                  
  :204   Cannot access property $inventory_item_id on mixed.                                                                     
         🪪  property.nonObject                                                                                                  
  :205   Cannot access property $inventoryItem on mixed.                                                                         
         🪪  property.nonObject                                                                                                  
  :205   Cannot access property $name on mixed.                                                                                  
         🪪  property.nonObject                                                                                                  
  :206   Cannot access property $generic_name on mixed.                                                                          
         🪪  property.nonObject                                                                                                  
  :206   Cannot access property $inventoryItem on mixed.                                                                         
         🪪  property.nonObject                                                                                                  
  :207   Cannot access property $brand_name on mixed.                                                                            
         🪪  property.nonObject                                                                                                  
  :207   Cannot access property $inventoryItem on mixed.                                                                         
         🪪  property.nonObject                                                                                                  
  :208   Cannot access property $inventoryItem on mixed.                                                                         
         🪪  property.nonObject                                                                                                  
  :208   Cannot access property $strength on mixed.                                                                              
         🪪  property.nonObject                                                                                                  
  :209   Cannot access property $dosage_form on mixed.                                                                           
         🪪  property.nonObject                                                                                                  
  :209   Cannot access property $dosage_form on mixed.                                                                           
         🪪  property.nonObject                                                                                                  
  :209   Cannot access property $inventoryItem on mixed.                                                                         
         🪪  property.nonObject                                                                                                  
  :209   Cannot access property $inventoryItem on mixed.                                                                         
         🪪  property.nonObject                                                                                                  
  :209   Cannot access property $value on mixed.                                                                                 
         🪪  property.nonObject                                                                                                  
  :209   Using nullsafe property access "?->value" on left side of ?? is unnecessary. Use -> instead.                            
         🪪  nullsafe.neverNull                                                                                                  
  :210   Cannot access property $dosage on mixed.                                                                                
         🪪  property.nonObject                                                                                                  
  :211   Cannot access property $frequency on mixed.                                                                             
         🪪  property.nonObject                                                                                                  
  :212   Cannot access property $route on mixed.                                                                                 
         🪪  property.nonObject                                                                                                  
  :213   Cannot access property $duration_days on mixed.                                                                         
         🪪  property.nonObject                                                                                                  
  :218   Cannot access property $instructions on mixed.                                                                          
         🪪  property.nonObject                                                                                                  
  :219   Cannot access property $status on mixed.                                                                                
         🪪  property.nonObject                                                                                                  
  :219   Cannot access property $value on mixed.                                                                                 
         🪪  property.nonObject                                                                                                  
  :220   Cannot access property $status on mixed.                                                                                
         🪪  property.nonObject                                                                                                  
  :220   Cannot call method label() on mixed.                                                                                    
         🪪  method.nonObject                                                                                                    
  :221   Cannot access property $dispensed_at on mixed.                                                                          
         🪪  property.nonObject                                                                                                  
  :221   Cannot call method toISOString() on mixed.                                                                              
         🪪  method.nonObject                                                                                                    
  :222   Cannot access property $is_external_pharmacy on mixed.                                                                  
         🪪  property.nonObject                                                                                                  
  :324   Using nullsafe property access "?->generic_name" on left side of ?? is unnecessary. Use -> instead.                     
         🪪  nullsafe.neverNull                                                                                                  



  Line   app\Http\Controllers\StaffController.php                                                                                

  :173   Parameter #1 $array of function array_unique expects an array of values castable to string, array<mixed, mixed> given.  
         🪪  argument.type                                                                                                       



  Line   app\Http\Controllers\SubscriptionActivationController.php              

  :73    Only iterables can be unpacked, array|string given.                    
         🪪  arrayUnpacking.nonIterable                                         
  :134   Only iterables can be unpacked, array|string given.                    
         🪪  arrayUnpacking.nonIterable                                         
  :168   Only iterables can be unpacked, array|string given.                    
         🪪  arrayUnpacking.nonIterable                                         
  :186   Cannot access property $value on string.                               
         🪪  property.nonObject                                                 
  :187   Cannot call method label() on string.                                  
         🪪  method.nonObject                                                   
  :197   Cannot access property $id on App\Models\SubscriptionPackage|null.     
         🪪  property.nonObject                                                 
  :198   Cannot access property $name on App\Models\SubscriptionPackage|null.   
         🪪  property.nonObject                                                 
  :199   Cannot access property $users on App\Models\SubscriptionPackage|null.  
         🪪  property.nonObject                                                 
  :200   Cannot access property $price on App\Models\SubscriptionPackage|null.  
         🪪  property.nonObject                                                 



  Line   app\Http\Controllers\VisitOrderController.php                                                                           

  :149   Result of || is always true.                                                                                            
         🪪  booleanOr.alwaysTrue                                                                                                
         💡  Because the type is coming from a PHPDoc, you can turn off this check by setting treatPhpDocTypesAsCertain: false i  
         n your phpstan.neon.                                                                                                    
         💡  If App\Http\Controllers\VisitOrderController::resolveStaffId() is impure, add @phpstan-impure PHPDoc tag above its   
         declaration. Learn more: https://phpstan.org/blog/remembering-and-forgetting-returned-values                            
  :149   Strict comparison using !== between App\Enums\LabRequestItemStatus and 'pending' will always evaluate to true.          
         🪪  notIdentical.alwaysTrue                                                                                             
         💡  Because the type is coming from a PHPDoc, you can turn off this check by setting treatPhpDocTypesAsCertain: false i  
         n your phpstan.neon.                                                                                                    
  :154   Unreachable statement - code above always terminates.                                                                   
         🪪  deadCode.unreachable                                                                                                



  Line   app\Http\Middleware\HandleInertiaRequests.php                                                                           

  :56    Anonymous function should return string|null but returns mixed.                                                         
         🪪  return.type                                                                                                         
  :57    Anonymous function should return string|null but returns mixed.                                                         
         🪪  return.type                                                                                                         
  :58    Anonymous function should return string|null but returns mixed.                                                         
         🪪  return.type                                                                                                         
  :59    Anonymous function should return string|null but returns mixed.                                                         
         🪪  return.type                                                                                                         
  :60    Anonymous function should return string|null but returns mixed.                                                         
         🪪  return.type                                                                                                         
  :83    Parameter #1 $relations of method Illuminate\Database\Eloquent\Builder<Illuminate\Database\Eloquent\Model>::with() exp  
         ects array<array|(Closure(Illuminate\Database\Eloquent\Relations\Relation<*, *, *>): mixed)|string>|string,             
         array{currentSubscription: Closure(Illuminate\Database\Eloquent\Relations\HasOne):                                      
         Illuminate\Database\Eloquent\Relations\HasOne} given.                                                                   
         🪪  argument.type                                                                                                       
  :92    Parameter #1 $relations of method Illuminate\Database\Eloquent\Builder<Illuminate\Database\Eloquent\Model>::with() exp  
         ects array<array|(Closure(Illuminate\Database\Eloquent\Relations\Relation<*, *, *>): mixed)|string>|string,             
         array{subscriptionPackage: Closure(Illuminate\Database\Eloquent\Relations\BelongsTo):                                   
         Illuminate\Database\Eloquent\Relations\BelongsTo} given.                                                                
         🪪  argument.type                                                                                                       
  :120   Parameter #1 $callback of method Illuminate\Support\Collection<(int|string),mixed>::mapWithKeys() expects               
         callable(mixed, int|string): array<string, true>, Closure(string): non-empty-array<string, true> given.                 
         🪪  argument.type                                                                                                       
         💡  Type string of parameter #1 $permission of passed callable needs to be same or wider than parameter type mixed of   
         accepting callable.                                                                                                     



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



  Line   app\Http\Requests\StoreConsultationPrescriptionRequest.php                                                            

  :58    Offset 'is_prn' on array{inventory_item_id: string, dosage: string, frequency: string, route: string, duration_days:  
         int, quantity: int, instructions: string|null, is_prn: bool, ...} on left side of ?? always exists and is not         
         nullable.                                                                                                             
         🪪  nullCoalesce.offset                                                                                     

  Line   app\Http\Requests\UpdateAppointmentCategoryRequest.php                                               

  :27    Using nullsafe property access on non-nullable type App\Models\AppointmentCategory. Use -> instead.  
         🪪  nullsafe.neverNull                                                                               

  Line   app\Http\Requests\UpdateAppointmentModeRequest.php                                               

  :27    Using nullsafe property access on non-nullable type App\Models\AppointmentMode. Use -> instead.  
         🪪  nullsafe.neverNull                                                                           

  Line   app\Http\Requests\UpdateStaffRequest.php                                               

  :24    Access to an undefined property (object|string|null)::$id.                             
         🪪  property.notFound                                                                  
         💡  Learn more: https://phpstan.org/blog/solving-phpstan-access-to-undefined-property  
  :30    Binary operation "." between 'unique:staff,email,' and mixed results in an error.      
         🪪  binaryOp.invalid                                                                   



  Line   app\Http\Requests\UpdateUnitRequest.php                                                

  :30    Access to an undefined property (object|string|null)::$id.                             
         🪪  property.notFound                                                                  
         💡  Learn more: https://phpstan.org/blog/solving-phpstan-access-to-undefined-property  
  :36    Access to an undefined property (object|string|null)::$id.                             
         🪪  property.notFound                                                                  
         💡  Learn more: https://phpstan.org/blog/solving-phpstan-access-to-undefined-property  



  Line   app\Support\PrescriptionDispenseProgress.php                                                                            

  :53    Method App\Support\PrescriptionDispenseProgress::postedLineSummaries() should return Illuminate\Support\Collection<str  
         ing, array{dispensed_quantity: float, external_quantity: float, covered_quantity: float, latest_dispensed_at:           
         Illuminate\Support\Carbon|null, external_pharmacy: bool}> but returns Illuminate\Support\Collection<string, array{disp  
         ensed_quantity: float, external_quantity: float, covered_quantity: float, latest_dispensed_at:                          
         Carbon\CarbonImmutable|null, external_pharmacy: bool}>.                                                                 
         🪪  return.type                                                                                  

  Line   database\seeders\SupportUserSeeder.php                                             

  :52    Cannot call method orderBy() on mixed.                                             
         🪪  method.nonObject                                                               
  :52    Cannot call method orderByDesc() on mixed.                                         
         🪪  method.nonObject                                                               
  :53    Cannot call method orderBy() on mixed.                                             
         🪪  method.nonObject                                                               
  :119   Parameter #1 $string of function mb_strtoupper expects string, string|null given.  
         🪪  argument.type                                                                                   
 [ERROR] Found 254 errors                                                                                               
                                                                                                                        