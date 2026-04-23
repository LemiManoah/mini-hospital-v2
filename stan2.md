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



  Line   app\Http\Controllers\PurchaseOrderController.php                                                       

  :100   Parameter #1 $attributes of method App\Actions\CreatePurchaseOrder::handle() expects                   
         array{tenant_id?: string, branch_id: string, supplier_id: string, order_date: string,                  
         expected_delivery_date?: string|null, notes?: string|null, approved_by?: string|null, approved_at?:    
         string|null, ...}, array<string, mixed> given.                                                         
         🪪  argument.type                                                                                      
  :100   Parameter #2 $items of method App\Actions\CreatePurchaseOrder::handle() expects list<array{inventory_  
         item_id: string, quantity_ordered: float|int|string, unit_cost: float|int|string}>, array<int, array{  
         inventory_item_id: string, quantity_ordered: float, unit_cost: float}> given.                          
         🪪  argument.type                                                                                      
         💡  array<int, array{inventory_item_id: string, quantity_ordered: float, unit_cost: float}> might not   
         be a list.                                                                                             



  Line   app\Http\Controllers\StaffController.php                                                               

  :173   Parameter #1 $array of function array_unique expects an array of values castable to string, array<mix  
         ed, mixed> given.                                                                                      
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



  Line   app\Http\Controllers\UserController.php                                                                

  :94    Parameter #1 $attributes of method App\Actions\CreateUser::handle() expects array{roles?: list<string  
         >}, array<string, mixed> given.                                                                        
         🪪  argument.type                                                                                      
         💡  Offset 'roles' (list<string>) does not accept type mixed: mixed is not a list.                     
  :121   Parameter #2 $attributes of method App\Actions\UpdateUser::handle() expects array{email?: string,      
         roles?: list<string>}, array<string, mixed> given.                                                     
         🪪  argument.type                                                                                      
         💡  Offset 'roles' (list<string>) does not accept type mixed: mixed is not a list.                     
  :137   Parameter #1 $user of method App\Actions\DeleteUser::handle() expects App\Models\User,                 
         App\Models\User|null given.                                                                            
         🪪  argument.type                                                                                      



  Line   app\Http\Controllers\UserProfileController.php                                                     

  :27    Parameter #2 $attributes of method App\Actions\UpdateUser::handle() expects array{email?: string,  
         roles?: list<string>}, array<string, mixed> given.                                                 
         🪪  argument.type                                                                                  
         💡  Offset 'roles' (list<string>) does not accept type mixed: mixed is not a list.                 



  Line   app\Http\Controllers\VisitOrderController.php                                                          

  :149   Result of || is always true.                                                                           
         🪪  booleanOr.alwaysTrue                                                                               
         💡  Because the type is coming from a PHPDoc, you can turn off this check by setting treatPhpDocTypesA  
         sCertain: false in your phpstan.neon.                                                                  
         💡  If App\Http\Controllers\VisitOrderController::resolveStaffId() is impure, add @phpstan-impure PHPD  
         oc tag above its declaration. Learn more: https://phpstan.org/blog/remembering-and-forgetting-returne  
         d-values                                                                                               
  :149   Strict comparison using !== between App\Enums\LabRequestItemStatus and 'pending' will always evaluate  
         to true.                                                                                               
         🪪  notIdentical.alwaysTrue                                                                            
         💡  Because the type is coming from a PHPDoc, you can turn off this check by setting treatPhpDocTypesA  
         sCertain: false in your phpstan.neon.                                                                  
  :154   Unreachable statement - code above always terminates.                                                  
         🪪  deadCode.unreachable                                                                               



  Line   app\Http\Controllers\VisitPaymentController.php                                                        

  :43    Parameter #2 $attributes of method App\Actions\RecordVisitPayment::handle() expects array<string, mix  
         ed>, mixed given.                                                                                      
         🪪  argument.type                                                                                      



  Line   app\Http\Controllers\WorkspaceRegistrationController.php                                               

  :58    Parameter #1 $attributes of method App\Actions\RegisterWorkspace::handle() expects array<string, mixe  
         d>, array given.                                                                                       
         🪪  argument.type                                                                                      



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
  :83    Parameter #1 $relations of method Illuminate\Database\Eloquent\Builder<Illuminate\Database\Eloquent\M  
         odel>::with() expects array<array|(Closure(Illuminate\Database\Eloquent\Relations\Relation<*, *, *>):  
          mixed)|string>|string, array{currentSubscription:                                                     
         Closure(Illuminate\Database\Eloquent\Relations\HasOne):                                                
         Illuminate\Database\Eloquent\Relations\HasOne} given.                                                  
         🪪  argument.type                                                                                      
  :92    Parameter #1 $relations of method Illuminate\Database\Eloquent\Builder<Illuminate\Database\Eloquent\M  
         odel>::with() expects array<array|(Closure(Illuminate\Database\Eloquent\Relations\Relation<*, *, *>):  
          mixed)|string>|string, array{subscriptionPackage:                                                     
         Closure(Illuminate\Database\Eloquent\Relations\BelongsTo):                                             
         Illuminate\Database\Eloquent\Relations\BelongsTo} given.                                               
         🪪  argument.type                                                                                      
  :120   Parameter #1 $callback of method Illuminate\Support\Collection<(int|string),mixed>::mapWithKeys()      
         expects callable(mixed, int|string): array<string, true>, Closure(string): non-empty-array<string, tr  
         ue> given.                                                                                             
         🪪  argument.type                                                                                      
         💡  Type string of parameter #1 $permission of passed callable needs to be same or wider than          
         parameter type mixed of accepting callable.                                                            



  Line   app\Http\Requests\CorrectLabResultEntryRequest.php                                                     

  :65    Strict comparison using !== between App\Models\LabTestCatalog and null will always evaluate to true.   
         🪪  notIdentical.alwaysTrue                                                                            
         💡  Because the type is coming from a PHPDoc, you can turn off this check by setting treatPhpDocTypesA  
         sCertain: false in your phpstan.neon.                                                                  
  :94    Strict comparison using !== between App\Models\LabTestCatalog and null will always evaluate to true.   
         🪪  notIdentical.alwaysTrue                                                                            
         💡  Because the type is coming from a PHPDoc, you can turn off this check by setting treatPhpDocTypesA  
         sCertain: false in your phpstan.neon.                                                                  



  Line   app\Http\Requests\DispensePrescriptionRequest.php                                                      

  :63    PHPDoc tag @return has invalid value (array<int, callable(\\Illuminate\\Validation\\Validator): void>  
         ): Unexpected token "(", expected '>' at offset 38 on line 2                                           
         🪪  phpDoc.parseError                                                                                  
  :65    Method App\Http\Requests\DispensePrescriptionRequest::after() return type has no value type specified  
         in iterable type array.                                                                                
         🪪  missingType.iterableValue                                                                          
         💡  See: https://phpstan.org/blog/solving-phpstan-no-value-type-specified-in-iterable-type             
  :70    Call to an undefined method (object|string)::getKey().                                                 
         🪪  method.notFound                                                                                    
  :79    Call to function is_array() with list<array{prescription_item_id: string, dispensed_quantity:          
         float|int|string, external_pharmacy: bool, external_reason: string|null, notes: string|null,           
         substitution_inventory_item_id: string|null, allocations: list<array{inventory_batch_id: string, quan  
         tity: float|int|string}>}> will always evaluate to true.                                               
         🪪  function.alreadyNarrowedType                                                                       
  :121   Cannot access property $tenant_id on App\Models\PatientVisit|null.                                     
         🪪  property.nonObject                                                                                 
  :121   Parameter #1 $tenantId of method App\Support\GeneralSettings\TenantGeneralSettings::boolean() expects  
         string, string|null given.                                                                             
         🪪  argument.type                                                                                      
  :127   Call to function is_array() with array{prescription_item_id: string, dispensed_quantity:               
         float|int|string, external_pharmacy: bool, external_reason: string|null, notes: string|null,           
         substitution_inventory_item_id: string|null, allocations: list<array{inventory_batch_id: string, quan  
         tity: float|int|string}>} will always evaluate to true.                                                
         🪪  function.alreadyNarrowedType                                                                       
         💡  Because the type is coming from a PHPDoc, you can turn off this check by setting treatPhpDocTypesA  
         sCertain: false in your phpstan.neon.                                                                  
  :131   Offset 'prescription_item_id' on array{prescription_item_id: string, dispensed_quantity:               
         float|int|string, external_pharmacy: bool, external_reason: string|null, notes: string|null,           
         substitution_inventory_item_id: string|null, allocations: list<array{inventory_batch_id: string, quan  
         tity: float|int|string}>} on left side of ?? always exists and is not nullable.                        
         🪪  nullCoalesce.offset                                                                                
  :132   Offset 'dispensed_quantity' on array{prescription_item_id: string, dispensed_quantity:                 
         float|int|string, external_pharmacy: bool, external_reason: string|null, notes: string|null,           
         substitution_inventory_item_id: string|null, allocations: list<array{inventory_batch_id: string, quan  
         tity: float|int|string}>} on left side of ?? always exists and is not nullable.                        
         🪪  nullCoalesce.offset                                                                                
  :133   Offset 'external_pharmacy' on array{prescription_item_id: string, dispensed_quantity:                  
         float|int|string, external_pharmacy: bool, external_reason: string|null, notes: string|null,           
         substitution_inventory_item_id: string|null, allocations: list<array{inventory_batch_id: string, quan  
         tity: float|int|string}>} on left side of ?? always exists and is not nullable.                        
         🪪  nullCoalesce.offset                                                                                
  :135   Call to function is_string() with string will always evaluate to true.                                 
         🪪  function.alreadyNarrowedType                                                                       
         💡  Because the type is coming from a PHPDoc, you can turn off this check by setting treatPhpDocTypesA  
         sCertain: false in your phpstan.neon.                                                                  
  :224   Call to function is_array() with list<array{prescription_item_id: string, dispensed_quantity:          
         float|int|string, external_pharmacy: bool, external_reason: string|null, notes: string|null,           
         substitution_inventory_item_id: string|null, allocations: list<array{inventory_batch_id: string, quan  
         tity: float|int|string}>}> will always evaluate to true.                                               
         🪪  function.alreadyNarrowedType                                                                       
  :231   Cannot access property $tenant_id on App\Models\PatientVisit|null.                                     
         🪪  property.nonObject                                                                                 
  :231   Parameter #1 $tenantId of method App\Support\GeneralSettings\TenantGeneralSettings::boolean() expects  
         string, string|null given.                                                                             
         🪪  argument.type                                                                                      
  :253   Call to function is_array() with array{prescription_item_id: string, dispensed_quantity:               
         float|int|string, external_pharmacy: bool, external_reason: string|null, notes: string|null,           
         substitution_inventory_item_id: string|null, allocations: list<array{inventory_batch_id: string, quan  
         tity: float|int|string}>} will always evaluate to true.                                                
         🪪  function.alreadyNarrowedType                                                                       
         💡  Because the type is coming from a PHPDoc, you can turn off this check by setting treatPhpDocTypesA  
         sCertain: false in your phpstan.neon.                                                                  
  :257   Offset 'prescription_item_id' on array{prescription_item_id: string, dispensed_quantity:               
         float|int|string, external_pharmacy: bool, external_reason: string|null, notes: string|null,           
         substitution_inventory_item_id: string|null, allocations: list<array{inventory_batch_id: string, quan  
         tity: float|int|string}>} on left side of ?? always exists and is not nullable.                        
         🪪  nullCoalesce.offset                                                                                
  :258   Offset 'dispensed_quantity' on array{prescription_item_id: string, dispensed_quantity:                 
         float|int|string, external_pharmacy: bool, external_reason: string|null, notes: string|null,           
         substitution_inventory_item_id: string|null, allocations: list<array{inventory_batch_id: string, quan  
         tity: float|int|string}>} on left side of ?? always exists and is not nullable.                        
         🪪  nullCoalesce.offset                                                                                
  :259   Offset 'allocations' on array{prescription_item_id: string, dispensed_quantity: float|int|string,      
         external_pharmacy: bool, external_reason: string|null, notes: string|null,                             
         substitution_inventory_item_id: string|null, allocations: list<array{inventory_batch_id: string, quan  
         tity: float|int|string}>} on left side of ?? always exists and is not nullable.                        
         🪪  nullCoalesce.offset                                                                                
  :261   Call to function is_string() with string will always evaluate to true.                                 
         🪪  function.alreadyNarrowedType                                                                       
         💡  Because the type is coming from a PHPDoc, you can turn off this check by setting treatPhpDocTypesA  
         sCertain: false in your phpstan.neon.                                                                  
  :279   Call to function is_array() with list<array{inventory_batch_id: string, quantity: float|int|string}>   
         will always evaluate to true.                                                                          
         🪪  function.alreadyNarrowedType                                                                       
         💡  Because the type is coming from a PHPDoc, you can turn off this check by setting treatPhpDocTypesA  
         sCertain: false in your phpstan.neon.                                                                  
  :291   Call to function is_array() with array{inventory_batch_id: string, quantity: float|int|string} will    
         always evaluate to true.                                                                               
         🪪  function.alreadyNarrowedType                                                                       
         💡  Because the type is coming from a PHPDoc, you can turn off this check by setting treatPhpDocTypesA  
         sCertain: false in your phpstan.neon.                                                                  
  :295   Offset 'inventory_batch_id' on array{inventory_batch_id: string, quantity: float|int|string} on left   
         side of ?? always exists and is not nullable.                                                          
         🪪  nullCoalesce.offset                                                                                
  :296   Offset 'quantity' on array{inventory_batch_id: string, quantity: float|int|string} on left side of ??  
         always exists and is not nullable.                                                                     
         🪪  nullCoalesce.offset                                                                                
  :298   Call to function is_string() with string will always evaluate to true.                                 
         🪪  function.alreadyNarrowedType                                                                       
         💡  Because the type is coming from a PHPDoc, you can turn off this check by setting treatPhpDocTypesA  
         sCertain: false in your phpstan.neon.                                                                  
  :450   Method App\Http\Requests\DispensePrescriptionRequest::normalizedItems() should return                  
         list<array{prescription_item_id: string, dispensed_quantity: float|int|string, external_pharmacy:      
         bool, external_reason: string|null, notes: string|null, substitution_inventory_item_id: string|null,   
         allocations: list<array{inventory_batch_id: string, quantity: float|int|string}>}> but returns list<a  
         rray{prescription_item_id: non-empty-string, dispensed_quantity: mixed, external_pharmacy: bool,       
         external_reason: string|null, notes: string|null, substitution_inventory_item_id: string|null,         
         allocations: list<array{inventory_batch_id: non-empty-string, quantity: float|int|numeric-string}>}>.  
         🪪  return.type                                                                                        
         💡  Offset 'dispensed_quantity' (float|int|string) does not accept type mixed.                         



  Line   app\Http\Requests\StoreConsultationFacilityServiceOrderRequest.php                                 

  :20    Method App\Http\Requests\StoreConsultationFacilityServiceOrderRequest::rules() return type has no  
         value type specified in iterable type array.                                                       
         🪪  missingType.iterableValue                                                                      
         💡  See: https://phpstan.org/blog/solving-phpstan-no-value-type-specified-in-iterable-type         



  Line   app\Http\Requests\StoreConsultationImagingRequest.php                                            

  :22    Method App\Http\Requests\StoreConsultationImagingRequest::rules() return type has no value type  
         specified in iterable type array.                                                                
         🪪  missingType.iterableValue                                                                    
         💡  See: https://phpstan.org/blog/solving-phpstan-no-value-type-specified-in-iterable-type       



  Line   app\Http\Requests\StoreConsultationLabRequest.php                                                      

  :20    Method App\Http\Requests\StoreConsultationLabRequest::rules() return type has no value type specified  
         in iterable type array.                                                                                
         🪪  missingType.iterableValue                                                                          
         💡  See: https://phpstan.org/blog/solving-phpstan-no-value-type-specified-in-iterable-type             



  Line   app\Http\Requests\StoreConsultationPrescriptionRequest.php                                             

  :58    Offset 'is_prn' on array{inventory_item_id: string, dosage: string, frequency: string, route: string,  
         duration_days: int, quantity: int, instructions: string|null, is_prn: bool, ...} on left side of ??    
         always exists and is not nullable.                                                                     
         🪪  nullCoalesce.offset                                                                                



  Line   app\Http\Requests\StoreDispenseRequest.php                                                             

  :65    Call to an undefined method (object|string)::getKey().                                                 
         🪪  method.notFound                                                                                    
  :124   Parameter #1 $tenantId of method App\Support\GeneralSettings\TenantGeneralSettings::boolean() expects  
         string, string|null given.                                                                             
         🪪  argument.type                                                                                      



  Line   app\Http\Requests\StoreInsurancePackageRequest.php  

  :44    Cannot cast mixed to string.                        
         🪪  cast.string                                     



  Line   app\Http\Requests\StoreInventoryItemRequest.php                                                        

  :111   Method App\Http\Requests\StoreInventoryItemRequest::numericOrDefault() should return float|int|string  
         but returns mixed.                                                                                     
         🪪  return.type                                                                                        



  Line   app\Http\Requests\StoreLabResultEntryRequest.php                                                       

  :50    Cannot cast mixed to string.                                                                           
         🪪  cast.string                                                                                        
  :58    Cannot cast mixed to string.                                                                           
         🪪  cast.string                                                                                        
  :59    Expression on left side of ?? is not nullable.                                                         
         🪪  nullCoalesce.expr                                                                                  
  :59    Using nullsafe method call on non-nullable type Illuminate\Database\Eloquent\Collection<int, App\Mode  
         ls\LabTestResultOption>. Use -> instead.                                                               
         🪪  nullsafe.neverNull                                                                                 
  :59    Using nullsafe property access on non-nullable type App\Models\LabTestCatalog. Use -> instead.         
         🪪  nullsafe.neverNull                                                                                 
  :72    Parameter #1 $value of function collect expects Illuminate\Contracts\Support\Arrayable<(int|string),   
         mixed>|iterable<(int|string), mixed>|null, mixed given.                                                
         🪪  argument.type                                                                                      
  :72    Unable to resolve the template type TKey in call to function collect                                   
         🪪  argument.templateType                                                                              
         💡  See: https://phpstan.org/blog/solving-phpstan-error-unable-to-resolve-template-type                
  :72    Unable to resolve the template type TValue in call to function collect                                 
         🪪  argument.templateType                                                                              
         💡  See: https://phpstan.org/blog/solving-phpstan-error-unable-to-resolve-template-type                
  :75    Cannot cast mixed to string.                                                                           
         🪪  cast.string                                                                                        
  :78    Expression on left side of ?? is not nullable.                                                         
         🪪  nullCoalesce.expr                                                                                  
  :80    Cannot cast mixed to string.                                                                           
         🪪  cast.string                                                                                        



  Line   app\Http\Requests\StoreLabTestCatalogRequest.php                                                      

  :98    Parameter #1 $value of function collect expects Illuminate\Contracts\Support\Arrayable<(int|string),  
         mixed>|iterable<(int|string), mixed>|null, mixed given.                                               
         🪪  argument.type                                                                                     
  :98    Unable to resolve the template type TKey in call to function collect                                  
         🪪  argument.templateType                                                                             
         💡  See: https://phpstan.org/blog/solving-phpstan-error-unable-to-resolve-template-type               
  :98    Unable to resolve the template type TValue in call to function collect                                
         🪪  argument.templateType                                                                             
         💡  See: https://phpstan.org/blog/solving-phpstan-error-unable-to-resolve-template-type               
  :117   Method App\Http\Requests\StoreLabTestCatalogRequest::selectedResultTypeCode() should return           
         string|null but returns mixed.                                                                        
         🪪  return.type                                                                                       
  :127   Method App\Http\Requests\StoreLabTestCatalogRequest::filledResultOptions() should return array<int,   
         array<string, mixed>> but returns array<int, array<mixed, mixed>>.                                    
         🪪  return.type                                                                                       
  :127   Parameter #1 $value of function collect expects Illuminate\Contracts\Support\Arrayable<(int|string),  
         mixed>|iterable<(int|string), mixed>|null, mixed given.                                               
         🪪  argument.type                                                                                     
  :127   Unable to resolve the template type TKey in call to function collect                                  
         🪪  argument.templateType                                                                             
         💡  See: https://phpstan.org/blog/solving-phpstan-error-unable-to-resolve-template-type               
  :127   Unable to resolve the template type TValue in call to function collect                                
         🪪  argument.templateType                                                                             
         💡  See: https://phpstan.org/blog/solving-phpstan-error-unable-to-resolve-template-type               
  :129   Cannot cast mixed to string.                                                                          
         🪪  cast.string                                                                                       
  :139   Method App\Http\Requests\StoreLabTestCatalogRequest::filledResultParameters() should return           
         array<int, array<string, mixed>> but returns array<int, array<mixed, mixed>>.                         
         🪪  return.type                                                                                       
  :139   Parameter #1 $value of function collect expects Illuminate\Contracts\Support\Arrayable<(int|string),  
         mixed>|iterable<(int|string), mixed>|null, mixed given.                                               
         🪪  argument.type                                                                                     
  :139   Unable to resolve the template type TKey in call to function collect                                  
         🪪  argument.templateType                                                                             
         💡  See: https://phpstan.org/blog/solving-phpstan-error-unable-to-resolve-template-type               
  :139   Unable to resolve the template type TValue in call to function collect                                
         🪪  argument.templateType                                                                             
         💡  See: https://phpstan.org/blog/solving-phpstan-error-unable-to-resolve-template-type               
  :141   Cannot cast mixed to string.                                                                          
         🪪  cast.string                                                                                       



  Line   app\Http\Requests\StorePatientRequest.php  

  :82    Cannot cast mixed to string.               
         🪪  cast.string                            



  Line   app\Http\Requests\UpdateAppointmentCategoryRequest.php                                               

  :27    Using nullsafe property access on non-nullable type App\Models\AppointmentCategory. Use -> instead.  
         🪪  nullsafe.neverNull                                                                               



  Line   app\Http\Requests\UpdateAppointmentModeRequest.php                                               

  :27    Using nullsafe property access on non-nullable type App\Models\AppointmentMode. Use -> instead.  
         🪪  nullsafe.neverNull                                                                           



  Line   app\Http\Requests\UpdateConsultationRequest.php  

  :63    Cannot cast mixed to string.                     
         🪪  cast.string                                  
  :67    Cannot cast mixed to string.                     
         🪪  cast.string                                  
  :72    Cannot cast mixed to string.                     
         🪪  cast.string                                  
  :73    Cannot cast mixed to string.                     
         🪪  cast.string                                  
  :78    Cannot cast mixed to string.                     
         🪪  cast.string                                  
  :83    Cannot cast mixed to string.                     
         🪪  cast.string                                  
  :84    Cannot cast mixed to string.                     
         🪪  cast.string                                  
  :90    Cannot cast mixed to string.                     
         🪪  cast.string                                  
  :95    Cannot cast mixed to string.                     
         🪪  cast.string                                  



  Line   app\Http\Requests\UpdateInsurancePackageRequest.php  

  :47    Cannot cast mixed to string.                         
         🪪  cast.string                                      



  Line   app\Http\Requests\UpdateInventoryItemRequest.php                                       

  :111   Method App\Http\Requests\UpdateInventoryItemRequest::numericOrDefault() should return  
         float|int|string but returns mixed.                                                    
         🪪  return.type                                                                        



  Line   app\Http\Requests\UpdateLabTestCatalogRequest.php                                                     

  :101   Parameter #1 $value of function collect expects Illuminate\Contracts\Support\Arrayable<(int|string),  
         mixed>|iterable<(int|string), mixed>|null, mixed given.                                               
         🪪  argument.type                                                                                     
  :101   Unable to resolve the template type TKey in call to function collect                                  
         🪪  argument.templateType                                                                             
         💡  See: https://phpstan.org/blog/solving-phpstan-error-unable-to-resolve-template-type               
  :101   Unable to resolve the template type TValue in call to function collect                                
         🪪  argument.templateType                                                                             
         💡  See: https://phpstan.org/blog/solving-phpstan-error-unable-to-resolve-template-type               
  :120   Method App\Http\Requests\UpdateLabTestCatalogRequest::selectedResultTypeCode() should return          
         string|null but returns mixed.                                                                        
         🪪  return.type                                                                                       
  :130   Method App\Http\Requests\UpdateLabTestCatalogRequest::filledResultOptions() should return array<int,  
         array<string, mixed>> but returns array<int, array<mixed, mixed>>.                                    
         🪪  return.type                                                                                       
  :130   Parameter #1 $value of function collect expects Illuminate\Contracts\Support\Arrayable<(int|string),  
         mixed>|iterable<(int|string), mixed>|null, mixed given.                                               
         🪪  argument.type                                                                                     
  :130   Unable to resolve the template type TKey in call to function collect                                  
         🪪  argument.templateType                                                                             
         💡  See: https://phpstan.org/blog/solving-phpstan-error-unable-to-resolve-template-type               
  :130   Unable to resolve the template type TValue in call to function collect                                
         🪪  argument.templateType                                                                             
         💡  See: https://phpstan.org/blog/solving-phpstan-error-unable-to-resolve-template-type               
  :132   Cannot cast mixed to string.                                                                          
         🪪  cast.string                                                                                       
  :142   Method App\Http\Requests\UpdateLabTestCatalogRequest::filledResultParameters() should return          
         array<int, array<string, mixed>> but returns array<int, array<mixed, mixed>>.                         
         🪪  return.type                                                                                       
  :142   Parameter #1 $value of function collect expects Illuminate\Contracts\Support\Arrayable<(int|string),  
         mixed>|iterable<(int|string), mixed>|null, mixed given.                                               
         🪪  argument.type                                                                                     
  :142   Unable to resolve the template type TKey in call to function collect                                  
         🪪  argument.templateType                                                                             
         💡  See: https://phpstan.org/blog/solving-phpstan-error-unable-to-resolve-template-type               
  :142   Unable to resolve the template type TValue in call to function collect                                
         🪪  argument.templateType                                                                             
         💡  See: https://phpstan.org/blog/solving-phpstan-error-unable-to-resolve-template-type               
  :144   Cannot cast mixed to string.                                                                          
         🪪  cast.string                                                                                       



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



  Line   app\Models\Allergen.php                                                                                

  :31    PHPDoc tag @use has invalid type Database\Factories\AllergenFactory.                                   
         🪪  class.notFound                                                                                     
  :31    Type Database\Factories\AllergenFactory in generic type                                                
         Illuminate\Database\Eloquent\Factories\HasFactory<Database\Factories\AllergenFactory> in PHPDoc tag @  
         use is not subtype of template type TFactory of Illuminate\Database\Eloquent\Factories\Factory of      
         trait Illuminate\Database\Eloquent\Factories\HasFactory.                                               
         🪪  generics.notSubtype                                                                                



  Line   app\Models\Appointment.php                                                                             

  :19    Class App\Models\Appointment uses generic trait Illuminate\Database\Eloquent\Factories\HasFactory but  
         does not specify its types: TFactory                                                                   
         🪪  missingType.generics                                                                               



  Line   app\Models\AppointmentCategory.php                                                          

  :17    Class App\Models\AppointmentCategory uses generic trait                                     
         Illuminate\Database\Eloquent\Factories\HasFactory but does not specify its types: TFactory  
         🪪  missingType.generics                                                                    



  Line   app\Models\AppointmentMode.php                                                                         

  :16    Class App\Models\AppointmentMode uses generic trait Illuminate\Database\Eloquent\Factories\HasFactory  
         but does not specify its types: TFactory                                                               
         🪪  missingType.generics                                                                               



  Line   app\Models\Clinic.php                                                                                  

  :18    Class App\Models\Clinic uses generic trait Illuminate\Database\Eloquent\Factories\HasFactory but does  
         not specify its types: TFactory                                                                        
         🪪  missingType.generics                                                                               



  Line   app\Models\Consultation.php                                                                         

  :18    Class App\Models\Consultation uses generic trait Illuminate\Database\Eloquent\Factories\HasFactory  
         but does not specify its types: TFactory                                                            
         🪪  missingType.generics                                                                            



  Line   app\Models\CurrencyExchangeRate.php                                                         

  :30    Class App\Models\CurrencyExchangeRate uses generic trait                                    
         Illuminate\Database\Eloquent\Factories\HasFactory but does not specify its types: TFactory  
         🪪  missingType.generics                                                                    



  Line   app\Models\Department.php                                                                              

  :20    PHPDoc tag @use has invalid type Database\Factories\DepartmentFactory.                                 
         🪪  class.notFound                                                                                     
  :20    Type Database\Factories\DepartmentFactory in generic type                                              
         Illuminate\Database\Eloquent\Factories\HasFactory<Database\Factories\DepartmentFactory> in PHPDoc tag  
          @use is not subtype of template type TFactory of Illuminate\Database\Eloquent\Factories\Factory of    
         trait Illuminate\Database\Eloquent\Factories\HasFactory.                                               
         🪪  generics.notSubtype                                                                                



  Line   app\Models\DoctorSchedule.php                                                                         

  :18    Class App\Models\DoctorSchedule uses generic trait Illuminate\Database\Eloquent\Factories\HasFactory  
         but does not specify its types: TFactory                                                              
         🪪  missingType.generics                                                                              



  Line   app\Models\DoctorScheduleException.php                                                      

  :18    Class App\Models\DoctorScheduleException uses generic trait                                 
         Illuminate\Database\Eloquent\Factories\HasFactory but does not specify its types: TFactory  
         🪪  missingType.generics                                                                    



  Line   app\Models\FacilityService.php                                                                         

  :17    Class App\Models\FacilityService uses generic trait Illuminate\Database\Eloquent\Factories\HasFactory  
         but does not specify its types: TFactory                                                               
         🪪  missingType.generics                                                                               



  Line   app\Models\FacilityServiceOrder.php                                                         

  :17    Class App\Models\FacilityServiceOrder uses generic trait                                    
         Illuminate\Database\Eloquent\Factories\HasFactory but does not specify its types: TFactory  
         🪪  missingType.generics                                                                    



  Line   app\Models\ImagingRequest.php                                                                         

  :19    Class App\Models\ImagingRequest uses generic trait Illuminate\Database\Eloquent\Factories\HasFactory  
         but does not specify its types: TFactory                                                              
         🪪  missingType.generics                                                                              



  Line   app\Models\InsuranceCompany.php                                                             

  :19    Class App\Models\InsuranceCompany uses generic trait                                        
         Illuminate\Database\Eloquent\Factories\HasFactory but does not specify its types: TFactory  
         🪪  missingType.generics                                                                    



  Line   app\Models\InsuranceCompanyInvoice.php                                                      

  :19    Class App\Models\InsuranceCompanyInvoice uses generic trait                                 
         Illuminate\Database\Eloquent\Factories\HasFactory but does not specify its types: TFactory  
         🪪  missingType.generics                                                                    



  Line   app\Models\InsuranceCompanyInvoicePayment.php                                               

  :17    Class App\Models\InsuranceCompanyInvoicePayment uses generic trait                          
         Illuminate\Database\Eloquent\Factories\HasFactory but does not specify its types: TFactory  
         🪪  missingType.generics                                                                    



  Line   app\Models\InsurancePackage.php                                                             

  :19    Class App\Models\InsurancePackage uses generic trait                                        
         Illuminate\Database\Eloquent\Factories\HasFactory but does not specify its types: TFactory  
         🪪  missingType.generics                                                                    



  Line   app\Models\InsurancePackagePrice.php                                                        

  :19    Class App\Models\InsurancePackagePrice uses generic trait                                   
         Illuminate\Database\Eloquent\Factories\HasFactory but does not specify its types: TFactory  
         🪪  missingType.generics                                                                    



  Line   app\Models\InventoryLocationItem.php                                                        

  :18    Class App\Models\InventoryLocationItem uses generic trait                                   
         Illuminate\Database\Eloquent\Factories\HasFactory but does not specify its types: TFactory  
         🪪  missingType.generics                                                                    



  Line   app\Models\LabRequestItemConsumable.php                                                     

  :16    Class App\Models\LabRequestItemConsumable uses generic trait                                
         Illuminate\Database\Eloquent\Factories\HasFactory but does not specify its types: TFactory  
         🪪  missingType.generics                                                                    



  Line   app\Models\LabResultEntry.php                                                                         

  :15    Class App\Models\LabResultEntry uses generic trait Illuminate\Database\Eloquent\Factories\HasFactory  
         but does not specify its types: TFactory                                                              
         🪪  missingType.generics                                                                              



  Line   app\Models\LabResultType.php                                                                         

  :16    Class App\Models\LabResultType uses generic trait Illuminate\Database\Eloquent\Factories\HasFactory  
         but does not specify its types: TFactory                                                             
         🪪  missingType.generics                                                                             



  Line   app\Models\LabResultValue.php                                                                         

  :15    Class App\Models\LabResultValue uses generic trait Illuminate\Database\Eloquent\Factories\HasFactory  
         but does not specify its types: TFactory                                                              
         🪪  missingType.generics                                                                              



  Line   app\Models\LabSpecimen.php                                                                             

  :15    Class App\Models\LabSpecimen uses generic trait Illuminate\Database\Eloquent\Factories\HasFactory but  
         does not specify its types: TFactory                                                                   
         🪪  missingType.generics                                                                               



  Line   app\Models\LabTestCatalog.php                                                                         

  :19    Class App\Models\LabTestCatalog uses generic trait Illuminate\Database\Eloquent\Factories\HasFactory  
         but does not specify its types: TFactory                                                              
         🪪  missingType.generics                                                                              



  Line   app\Models\LabTestCategory.php                                                                         

  :16    Class App\Models\LabTestCategory uses generic trait Illuminate\Database\Eloquent\Factories\HasFactory  
         but does not specify its types: TFactory                                                               
         🪪  missingType.generics                                                                               



  Line   app\Models\LabTestResultOption.php                                                          

  :14    Class App\Models\LabTestResultOption uses generic trait                                     
         Illuminate\Database\Eloquent\Factories\HasFactory but does not specify its types: TFactory  
         🪪  missingType.generics                                                                    



  Line   app\Models\LabTestResultParameter.php                                                       

  :14    Class App\Models\LabTestResultParameter uses generic trait                                  
         Illuminate\Database\Eloquent\Factories\HasFactory but does not specify its types: TFactory  
         🪪  missingType.generics                                                                    



  Line   app\Models\Patient.php                                                                             

  :19    Class App\Models\Patient uses generic trait Illuminate\Database\Eloquent\Factories\HasFactory but  
         does not specify its types: TFactory                                                               
         🪪  missingType.generics                                                                           



  Line   app\Models\PatientAllergy.php                                                                         

  :21    Class App\Models\PatientAllergy uses generic trait Illuminate\Database\Eloquent\Factories\HasFactory  
         but does not specify its types: TFactory                                                              
         🪪  missingType.generics                                                                              



  Line   app\Models\Permission.php                                                                             

  :13    Class App\Models\Permission uses generic trait Illuminate\Database\Eloquent\Factories\HasFactory but  
         does not specify its types: TFactory                                                                  
         🪪  missingType.generics                                                                              



  Line   app\Models\Reconciliation.php                                                                         

  :21    Class App\Models\Reconciliation uses generic trait Illuminate\Database\Eloquent\Factories\HasFactory  
         but does not specify its types: TFactory                                                              
         🪪  missingType.generics                                                                              



  Line   app\Models\ReconciliationItem.php                                                           

  :35    Class App\Models\ReconciliationItem uses generic trait                                      
         Illuminate\Database\Eloquent\Factories\HasFactory but does not specify its types: TFactory  
         🪪  missingType.generics                                                                    



  Line   app\Models\Role.php                                                                                  

  :13    Class App\Models\Role uses generic trait Illuminate\Database\Eloquent\Factories\HasFactory but does  
         not specify its types: TFactory                                                                      
         🪪  missingType.generics                                                                             



  Line   app\Models\SpecimenType.php                                                                         

  :28    Class App\Models\SpecimenType uses generic trait Illuminate\Database\Eloquent\Factories\HasFactory  
         but does not specify its types: TFactory                                                            
         🪪  missingType.generics                                                                            



  Line   app\Models\StaffPosition.php                                                                         

  :17    Class App\Models\StaffPosition uses generic trait Illuminate\Database\Eloquent\Factories\HasFactory  
         but does not specify its types: TFactory                                                             
         🪪  missingType.generics                                                                             



  Line   app\Models\TenantGeneralSetting.php                                                         

  :24    Class App\Models\TenantGeneralSetting uses generic trait                                    
         Illuminate\Database\Eloquent\Factories\HasFactory but does not specify its types: TFactory  
         🪪  missingType.generics                                                                    



  Line   app\Models\TenantSubscription.php                                                           

  :15    Class App\Models\TenantSubscription uses generic trait                                      
         Illuminate\Database\Eloquent\Factories\HasFactory but does not specify its types: TFactory  
         🪪  missingType.generics                                                                    



  Line   app\Models\TenantSupportNote.php                                                            

  :14    Class App\Models\TenantSupportNote uses generic trait                                       
         Illuminate\Database\Eloquent\Factories\HasFactory but does not specify its types: TFactory  
         🪪  missingType.generics                                                                    



  Line   app\Models\TriageRecord.php                                                                         

  :46    Class App\Models\TriageRecord uses generic trait Illuminate\Database\Eloquent\Factories\HasFactory  
         but does not specify its types: TFactory                                                            
         🪪  missingType.generics                                                                            



  Line   app\Models\Unit.php                                                                                  

  :30    Class App\Models\Unit uses generic trait Illuminate\Database\Eloquent\Factories\HasFactory but does  
         not specify its types: TFactory                                                                      
         🪪  missingType.generics                                                                             



  Line   app\Models\VisitBilling.php                                                                         

  :49    Class App\Models\VisitBilling uses generic trait Illuminate\Database\Eloquent\Factories\HasFactory  
         but does not specify its types: TFactory                                                            
         🪪  missingType.generics                                                                            



  Line   app\Models\VisitCharge.php                                                                             

  :43    Class App\Models\VisitCharge uses generic trait Illuminate\Database\Eloquent\Factories\HasFactory but  
         does not specify its types: TFactory                                                                   
         🪪  missingType.generics                                                                               



  Line   app\Models\VisitPayer.php                                                                             

  :35    Class App\Models\VisitPayer uses generic trait Illuminate\Database\Eloquent\Factories\HasFactory but  
         does not specify its types: TFactory                                                                  
         🪪  missingType.generics                                                                              



  Line   app\Models\VitalSign.php                                                                             

  :40    Class App\Models\VitalSign uses generic trait Illuminate\Database\Eloquent\Factories\HasFactory but  
         does not specify its types: TFactory                                                                 
         🪪  missingType.generics                                                                             



  Line   app\Support\PrescriptionDispenseProgress.php                                                           

  :53    Method App\Support\PrescriptionDispenseProgress::postedLineSummaries() should return                   
         Illuminate\Support\Collection<string, array{dispensed_quantity: float, external_quantity: float, cove  
         red_quantity: float, latest_dispensed_at: Illuminate\Support\Carbon|null, external_pharmacy: bool}> b  
         ut returns Illuminate\Support\Collection<string, array{dispensed_quantity: float, external_quantity:   
         float, covered_quantity: float, latest_dispensed_at: Carbon\CarbonImmutable|null, external_pharmacy:   
         bool}>.                                                                                                
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



     Error                                                                                                 

     Ignored error pattern #PHPDoc tag @use has invalid type Database\\\\Factories\\\\.*Factory# in path   
     C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\app\Models\* was not matched in   
     reported errors.                                                                                      
     Ignored error pattern #Type Database\\\\Factories\\\\.*Factory in generic type                        
     Illuminate\\\\Database\\\\Eloquent\\\\Factories\\\\HasFactory<Database\\\\Factories\\\\.*Factory> in  
     PHPDoc tag @use is not subtype of template type TFactory.*HasFactory# in path                         
     C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2\app\Models\* was not matched in   
     reported errors.                                                                                      


                                                                                                               
 [ERROR] Found 463 errors                                                                                      
                                                                                                               
