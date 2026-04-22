🪪  nullsafe.neverNull                                                                                              
  :173   Using nullsafe property access on non-nullable type App\Models\InventoryLocation. Use -> instead.                   
         🪪  nullsafe.neverNull                                                                                              
  :190   Using nullsafe property access on non-nullable type App\Enums\InventoryItemType. Use -> instead.                    
         🪪  nullsafe.neverNull                                                                                              
  :198   Call to function is_string() with string will always evaluate to true.                                              
         🪪  function.alreadyNarrowedType                                                                                    
         💡  Because the type is coming from a PHPDoc, you can turn off this check by setting treatPhpDocTypesAsCertain: fal  
         se in your phpstan.neon.                                                                                            
  :213   Using nullsafe property access on non-nullable type App\Enums\InventoryItemType. Use -> instead.                    
         🪪  nullsafe.neverNull                                                                                              
  :250   Using nullsafe property access on non-nullable type App\Enums\InventoryItemType. Use -> instead.                    
         🪪  nullsafe.neverNull                                                                                              
  :252   Using nullsafe property access "?->minimum_stock_level" on left side of ?? is unnecessary. Use -> instead.          
         🪪  nullsafe.neverNull                                                                                              
  :258   Cannot access offset non-empty-string on mixed.                                                                     
         🪪  offsetAccess.nonOffsetAccessible                                                                                
  :259   Binary operation "+=" between mixed and float results in an error.                                                  
         🪪  assignOp.invalid                                                                                                
  :262   Method App\Http\Controllers\InventoryStockByLocationController::buildRows() should return array{rows:               
         Illuminate\Support\Collection<int, array<string, mixed>>, locations: Illuminate\Support\Collection<int, array<stri  
         ng, mixed>>} but returns array{rows: Illuminate\Support\Collection<int, non-empty-array<string, mixed>>, locations  
         : Illuminate\Support\Collection<int, array{id: string, name: string, code: string, type: 'laboratory'|'main_store'  
         |'other'|'pharmacy'|'procedure_room'|'satellite_store'|'ward_store', label: non-falsy-string}>}.                    
         🪪  return.type                                                                                                     
         💡  Offset 'rows' (Illuminate\Support\Collection<int, array<string, mixed>>) does not accept type Illuminate\Suppor  
         t\Collection<int, non-empty-array<string, mixed>>: Template type TValue on class Illuminate\Support\Collection is   
         not covariant. Learn more: https://phpstan.org/blog/whats-up-with-template-covariant                                
  :267   Cannot access offset string on mixed.                                                                               
         🪪  offsetAccess.nonOffsetAccessible                                                                                
  :267   Cannot cast mixed to float.                                                                                         
         🪪  cast.double                                                                                                     
 ------ -------------------------------------------------------------------------------------------------------------------- 

 ------ -------------------------------------------------------------------------------------------------------------------- 
  Line   app\Http\Controllers\LabRequestItemConsumableController.php                                                         
 ------ -------------------------------------------------------------------------------------------------------------------- 
  :72    Cannot cast mixed to float.                                                                                         
         🪪  cast.double                                                                                                     
  :83    Using nullsafe property access on non-nullable type App\Enums\InventoryItemType. Use -> instead.                    
         🪪  nullsafe.neverNull                                                                                              
  :110   Parameter #1 $model of method App\Support\ActiveBranchWorkspace::authorizeModel() expects                           
         Illuminate\Database\Eloquent\Model, App\Models\LabRequest|null given.                                               
         🪪  argument.type                                                                                                   
  :119   Parameter #2 $attributes of method App\Actions\RecordLabRequestItemConsumable::handle() expects                     
         array{consumable_name: string, unit_label?: string|null, quantity: float|int|string, unit_cost: float|int|string,   
         notes?: string|null, used_at?: Carbon\CarbonInterface|string|null, inventory_item_id?: string|null}, array<string,  
          mixed> given.                                                                                                      
         🪪  argument.type                                                                                                   
  :130   Parameter #1 $model of method App\Support\ActiveBranchWorkspace::authorizeModel() expects                           
         Illuminate\Database\Eloquent\Model, App\Models\LabRequest|null given.                                               
         🪪  argument.type                                                                                                   
 ------ -------------------------------------------------------------------------------------------------------------------- 

 ------ -------------------------------------------------------------------------------------------------------------------- 
  Line   app\Http\Controllers\LabResultWorkflowController.php                                                                
 ------ -------------------------------------------------------------------------------------------------------------------- 
  :105   Parameter #4 $successMessage of method App\Http\Controllers\LabResultWorkflowController::handleAction() expects     
         (Closure(mixed): string)|string, Closure(App\Models\LabRequestItem): ('Lab results reviewed and released            
         successfully.'|'Lab results reviewed successfully.') given.                                                         
         🪪  argument.type                                                                                                   
         💡  Type #1 from the union: Type App\Models\LabRequestItem of parameter #1 $updatedItem of passed callable needs to  
         be same or wider than parameter type mixed of accepting callable.                                                   
 ------ -------------------------------------------------------------------------------------------------------------------- 

 ------ --------------------------------------------------------------------------------------------------------------- 
  Line   app\Http\Controllers\LabTestCatalogController.php                                                              
 ------ --------------------------------------------------------------------------------------------------------------- 
  :79    Parameter #1 $attributes of method App\Actions\CreateLabTestCatalog::handle() expects array{tenant_id?:        
         string|null, test_code: string, test_name: string, lab_test_category_id: string, result_type_id: string,       
         description?: string|null, base_price?: float|int|string, is_active?: bool, ...}, array<string, mixed> given.  
         🪪  argument.type                                                                                              
         💡  Offset 'specimen_type_ids' (list<string>) does not accept type mixed: mixed is not a list.                 
         💡  Offset 'result_options' (list<array<string, mixed>>) does not accept type mixed: mixed is not a list.      
         💡  Offset 'result_parameters' (list<array<string, mixed>>) does not accept type mixed: mixed is not a list.   
 ------ --------------------------------------------------------------------------------------------------------------- 

 ------ -------------------------------------------------------------------------------------------------------------------- 
  Line   app\Http\Controllers\LaboratoryDashboardController.php                                                              
 ------ -------------------------------------------------------------------------------------------------------------------- 
  :99    Access to an undefined property App\Models\StockMovement::$total_qty.                                               
         🪪  property.notFound                                                                                               
         💡  Learn more: https://phpstan.org/blog/solving-phpstan-access-to-undefined-property                               
  :99    Cannot cast mixed to float.                                                                                         
         🪪  cast.double                                                                                                     
  :105   Cannot cast mixed to float.                                                                                         
         🪪  cast.double                                                                                                     
  :210   Parameter #1 $relations of method Illuminate\Database\Eloquent\Builder<App\Models\LabRequest>::with() expects arra  
         y<array|(Closure(Illuminate\Database\Eloquent\Relations\Relation<*, *, *>): mixed)|string>|string, array{0:         
         'requestedBy:id…', 1: 'visit:id,visit…', 2: 'visit.patient:id…', items:                                             
         Closure(Illuminate\Database\Eloquent\Relations\HasMany): Illuminate\Database\Eloquent\Relations\HasMany} given.     
         🪪  argument.type                                                                                                   
  :236   Method App\Http\Controllers\LaboratoryDashboardController::itemQuery() return type with generic class               
         Illuminate\Database\Eloquent\Builder does not specify its types: TModel                                             
         🪪  missingType.generics                                                                                            
 ------ -------------------------------------------------------------------------------------------------------------------- 

 ------ -------------------------------------------------------------------------------------------------------------------- 
  Line   app\Http\Controllers\LaboratoryQueueController.php                                                                  
 ------ -------------------------------------------------------------------------------------------------------------------- 
  :75    Anonymous function should return Illuminate\Database\Eloquent\Builder but returns                                   
         Illuminate\Database\Eloquent\Builder|Illuminate\Database\Eloquent\Relations\HasMany.                                
         🪪  return.type                                                                                                     
  :76    Parameter #1 $relations of method Illuminate\Database\Eloquent\Builder<App\Models\LabRequest>::with() expects arra  
         y<array|(Closure(Illuminate\Database\Eloquent\Relations\Relation<*, *, *>): mixed)|string>|string, array{0:         
         'requestedBy:id…', 1: 'visit:id,visit…', 2: 'visit.patient:id…', items:                                             
         Closure(Illuminate\Database\Eloquent\Relations\HasMany): void} given.                                               
         🪪  argument.type                                                                                                   
  :121   Method App\Http\Controllers\LaboratoryQueueController::applyStageFilter() has parameter $query with generic class   
         Illuminate\Database\Eloquent\Builder but does not specify its types: TModel                                         
         🪪  missingType.generics                                                                                            
  :121   Method App\Http\Controllers\LaboratoryQueueController::applyStageFilter() has parameter $query with generic class   
         Illuminate\Database\Eloquent\Relations\HasMany but does not specify its types: TRelatedModel, TDeclaringModel       
         🪪  missingType.generics                                                                                            
  :121   Method App\Http\Controllers\LaboratoryQueueController::applyStageFilter() return type with generic class            
         Illuminate\Database\Eloquent\Builder does not specify its types: TModel                                             
         🪪  missingType.generics                                                                                            
  :121   Method App\Http\Controllers\LaboratoryQueueController::applyStageFilter() return type with generic class            
         Illuminate\Database\Eloquent\Relations\HasMany does not specify its types: TRelatedModel, TDeclaringModel           
         🪪  missingType.generics                                                                                            
 ------ -------------------------------------------------------------------------------------------------------------------- 

 ------ ----------------------------------------------------------------------------------------------------------------- 
  Line   app\Http\Controllers\LaboratoryWorklistController.php                                                            
 ------ ----------------------------------------------------------------------------------------------------------------- 
  :69    Parameter #1 $tenantId of method App\Support\VisitWorkflowGuard::labReleasePolicy() expects string, string|null  
         given.                                                                                                           
         🪪  argument.type                                                                                                
 ------ ----------------------------------------------------------------------------------------------------------------- 

 ------ -------------------------------------------------------------------------------------------------------------------- 
  Line   app\Http\Controllers\OnboardingController.php                                                                       
 ------ -------------------------------------------------------------------------------------------------------------------- 
  :58    Call to function is_string() with string will always evaluate to true.                                              
         🪪  function.alreadyNarrowedType                                                                                    
         💡  Because the type is coming from a PHPDoc, you can turn off this check by setting treatPhpDocTypesAsCertain: fal  
         se in your phpstan.neon.                                                                                            
 ------ -------------------------------------------------------------------------------------------------------------------- 

 ------ -------------------------------------------------------------------------------------------------------------------- 
  Line   app\Http\Controllers\PatientController.php                                                                          
 ------ -------------------------------------------------------------------------------------------------------------------- 
  :123   Parameter #1 $cases of method App\Http\Controllers\PatientController::enumOptions() expects array<int, object{valu  
         e: string, label: string}>, array<int, App\Enums\Gender::FEMALE|App\Enums\Gender::MALE> given.                      
         🪪  argument.type                                                                                                   
         💡  App\Enums\Gender::MALE does not have property $label.                                                           
         💡  App\Enums\Gender::FEMALE does not have property $label.                                                         
  :124   Parameter #1 $cases of method App\Http\Controllers\PatientController::enumOptions() expects array<int, object{valu  
         e: string, label: string}>, array<int, App\Enums\MaritalStatus::DIVORCED|App\Enums\MaritalStatus::MARRIED|App\Enum  
         s\MaritalStatus::SEPARATED|App\Enums\MaritalStatus::SINGLE|App\Enums\MaritalStatus::WIDOWED> given.                 
         🪪  argument.type                                                                                                   
         💡  App\Enums\MaritalStatus::SINGLE does not have property $label.                                                  
         💡  App\Enums\MaritalStatus::MARRIED does not have property $label.                                                 
         💡  App\Enums\MaritalStatus::DIVORCED does not have property $label.                                                
         💡  App\Enums\MaritalStatus::WIDOWED does not have property $label.                                                 
         💡  App\Enums\MaritalStatus::SEPARATED does not have property $label.                                               
  :125   Parameter #1 $cases of method App\Http\Controllers\PatientController::enumOptions() expects array<int, object{valu  
         e: string, label: string}>, array<int, App\Enums\BloodGroup::A_NEGATIVE|App\Enums\BloodGroup::A_POSITIVE|App\Enums  
         \BloodGroup::AB_NEGATIVE|App\Enums\BloodGroup::AB_POSITIVE|App\Enums\BloodGroup::B_NEGATIVE|App\Enums\BloodGroup::  
         B_POSITIVE|App\Enums\BloodGroup::O_NEGATIVE|App\Enums\BloodGroup::O_POSITIVE|App\Enums\BloodGroup::UNKNOWN> given.  
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
  :126   Parameter #1 $cases of method App\Http\Controllers\PatientController::enumOptions() expects array<int, object{valu  
         e: string, label: string}>, array<int, App\Enums\Religion::BUDDHIST|App\Enums\Religion::CHRISTIAN|App\Enums\Religi  
         on::HINDU|App\Enums\Religion::MUSLIM|App\Enums\Religion::OTHER|App\Enums\Religion::UNKNOWN> given.                  
         🪪  argument.type                                                                                                   
         💡  App\Enums\Religion::CHRISTIAN does not have property $label.                                                    
         💡  App\Enums\Religion::MUSLIM does not have property $label.                                                       
         💡  App\Enums\Religion::HINDU does not have property $label.                                                        
         💡  App\Enums\Religion::BUDDHIST does not have property $label.                                                     
         💡  App\Enums\Religion::OTHER does not have property $label.                                                        
         💡  App\Enums\Religion::UNKNOWN does not have property $label.                                                      
  :127   Parameter #1 $cases of method App\Http\Controllers\PatientController::enumOptions() expects array<int, object{valu  
         e: string, label: string}>, array<int, App\Enums\KinRelationship::CHILD|App\Enums\KinRelationship::OTHER|App\Enums  
         \KinRelationship::PARENT|App\Enums\KinRelationship::SIBLING|App\Enums\KinRelationship::SPOUSE|App\Enums\KinRelatio  
         nship::UNKNOWN> given.                                                                                              
         🪪  argument.type                                                                                                   
         💡  App\Enums\KinRelationship::SPOUSE does not have property $label.                                                
         💡  App\Enums\KinRelationship::PARENT does not have property $label.                                                
         💡  App\Enums\KinRelationship::CHILD does not have property $label.                                                 
         💡  App\Enums\KinRelationship::SIBLING does not have property $label.                                               
         💡  App\Enums\KinRelationship::OTHER does not have property $label.                                                 
         💡  App\Enums\KinRelationship::UNKNOWN does not have property $label.                                               
  :161   Parameter #1 $cases of method App\Http\Controllers\PatientController::enumOptions() expects array<int, object{valu  
         e: string, label: string}>, array<int, App\Enums\Gender::FEMALE|App\Enums\Gender::MALE> given.                      
         🪪  argument.type                                                                                                   
         💡  App\Enums\Gender::MALE does not have property $label.                                                           
         💡  App\Enums\Gender::FEMALE does not have property $label.                                                         
  :162   Parameter #1 $cases of method App\Http\Controllers\PatientController::enumOptions() expects array<int, object{valu  
         e: string, label: string}>, array<int, App\Enums\MaritalStatus::DIVORCED|App\Enums\MaritalStatus::MARRIED|App\Enum  
         s\MaritalStatus::SEPARATED|App\Enums\MaritalStatus::SINGLE|App\Enums\MaritalStatus::WIDOWED> given.                 
         🪪  argument.type                                                                                                   
         💡  App\Enums\MaritalStatus::SINGLE does not have property $label.                                                  
         💡  App\Enums\MaritalStatus::MARRIED does not have property $label.                                                 
         💡  App\Enums\MaritalStatus::DIVORCED does not have property $label.                                                
         💡  App\Enums\MaritalStatus::WIDOWED does not have property $label.                                                 
         💡  App\Enums\MaritalStatus::SEPARATED does not have property $label.                                               
  :163   Parameter #1 $cases of method App\Http\Controllers\PatientController::enumOptions() expects array<int, object{valu  
         e: string, label: string}>, array<int, App\Enums\BloodGroup::A_NEGATIVE|App\Enums\BloodGroup::A_POSITIVE|App\Enums  
         \BloodGroup::AB_NEGATIVE|App\Enums\BloodGroup::AB_POSITIVE|App\Enums\BloodGroup::B_NEGATIVE|App\Enums\BloodGroup::  
         B_POSITIVE|App\Enums\BloodGroup::O_NEGATIVE|App\Enums\BloodGroup::O_POSITIVE|App\Enums\BloodGroup::UNKNOWN> given.  
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
  :164   Parameter #1 $cases of method App\Http\Controllers\PatientController::enumOptions() expects array<int, object{valu  
         e: string, label: string}>, array<int, App\Enums\Religion::BUDDHIST|App\Enums\Religion::CHRISTIAN|App\Enums\Religi  
         on::HINDU|App\Enums\Religion::MUSLIM|App\Enums\Religion::OTHER|App\Enums\Religion::UNKNOWN> given.                  
         🪪  argument.type                                                                                                   
         💡  App\Enums\Religion::CHRISTIAN does not have property $label.                                                    
         💡  App\Enums\Religion::MUSLIM does not have property $label.                                                       
         💡  App\Enums\Religion::HINDU does not have property $label.                                                        
         💡  App\Enums\Religion::BUDDHIST does not have property $label.                                                     
         💡  App\Enums\Religion::OTHER does not have property $label.                                                        
         💡  App\Enums\Religion::UNKNOWN does not have property $label.                                                      
  :165   Parameter #1 $cases of method App\Http\Controllers\PatientController::enumOptions() expects array<int, object{valu  
         e: string, label: string}>, array<int, App\Enums\KinRelationship::CHILD|App\Enums\KinRelationship::OTHER|App\Enums  
         \KinRelationship::PARENT|App\Enums\KinRelationship::SIBLING|App\Enums\KinRelationship::SPOUSE|App\Enums\KinRelatio  
         nship::UNKNOWN> given.                                                                                              
         🪪  argument.type                                                                                                   
         💡  App\Enums\KinRelationship::SPOUSE does not have property $label.                                                
         💡  App\Enums\KinRelationship::PARENT does not have property $label.                                                
         💡  App\Enums\KinRelationship::CHILD does not have property $label.                                                 
         💡  App\Enums\KinRelationship::SIBLING does not have property $label.                                               
         💡  App\Enums\KinRelationship::OTHER does not have property $label.                                                 
         💡  App\Enums\KinRelationship::UNKNOWN does not have property $label.                                               
  :240   Call to an undefined method object{value: string, label: string}::label().                                          
         🪪  method.notFound                                                                                                 
  :244   Method App\Http\Controllers\PatientController::enumOptions() should return array<int, array{value: string, label:   
         string}> but returns list<array{value: string, label: mixed}>.                                                      
         🪪  return.type                                                                                                     
         💡  Offset 'label' (string) does not accept type mixed.                                                             
 ------ -------------------------------------------------------------------------------------------------------------------- 

 ------ -------------------------------------------------------------------------------------------------------------------- 
  Line   app\Http\Controllers\PatientVisitController.php                                                                     
 ------ -------------------------------------------------------------------------------------------------------------------- 
  :205   Parameter #1 $cases of method App\Http\Controllers\PatientVisitController::enumOptions() expects array<int, object  
         {value: string, label: string}>, array<int, App\Enums\TriageGrade::BLACK|App\Enums\TriageGrade::GREEN|App\Enums\Tr  
         iageGrade::RED|App\Enums\TriageGrade::YELLOW> given.                                                                
         🪪  argument.type                                                                                                   
         💡  App\Enums\TriageGrade::RED does not have property $label.                                                       
         💡  App\Enums\TriageGrade::YELLOW does not have property $label.                                                    
         💡  App\Enums\TriageGrade::GREEN does not have property $label.                                                     
         💡  App\Enums\TriageGrade::BLACK does not have property $label.                                                     
  :206   Parameter #1 $cases of method App\Http\Controllers\PatientVisitController::enumOptions() expects array<int, object  
         {value: string, label: string}>, array<int, App\Enums\AttendanceType::NEW|App\Enums\AttendanceType::RE_ATTENDANCE|  
         App\Enums\AttendanceType::REFERRAL> given.                                                                          
         🪪  argument.type                                                                                                   
         💡  App\Enums\AttendanceType::NEW does not have property $label.                                                    
         💡  App\Enums\AttendanceType::RE_ATTENDANCE does not have property $label.                                          
         💡  App\Enums\AttendanceType::REFERRAL does not have property $label.                                               
  :207   Parameter #1 $cases of method App\Http\Controllers\PatientVisitController::enumOptions() expects array<int, object  
         {value: string, label: string}>, array<int, App\Enums\ConsciousLevel::ALERT|App\Enums\ConsciousLevel::PAIN|App\Enu  
         ms\ConsciousLevel::UNRESPONSIVE|App\Enums\ConsciousLevel::VOICE> given.                                             
         🪪  argument.type                                                                                                   
         💡  App\Enums\ConsciousLevel::ALERT does not have property $label.                                                  
         💡  App\Enums\ConsciousLevel::VOICE does not have property $label.                                                  
         💡  App\Enums\ConsciousLevel::PAIN does not have property $label.                                                   
         💡  App\Enums\ConsciousLevel::UNRESPONSIVE does not have property $label.                                           
  :208   Parameter #1 $cases of method App\Http\Controllers\PatientVisitController::enumOptions() expects array<int, object  
         {value: string, label: string}>, array<int, App\Enums\MobilityStatus::ASSISTED|App\Enums\MobilityStatus::INDEPENDE  
         NT|App\Enums\MobilityStatus::STRETCHER|App\Enums\MobilityStatus::WHEELCHAIR> given.                                 
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
  :368   Parameter #2 $redirectTo of method App\Http\Controllers\PatientVisitController::statusRedirect() expects string,    
         mixed given.                                                                                                        
         🪪  argument.type                                                                                                   
  :378   Parameter #2 $redirectTo of method App\Http\Controllers\PatientVisitController::statusRedirect() expects string,    
         mixed given.                                                                                                        
         🪪  argument.type                                                                                                   
  :382   Parameter #1 $value of static method App\Enums\VisitStatus::from() expects int|string, mixed given.                 
         🪪  argument.type                                                                                                   
  :384   Parameter #2 $redirectTo of method App\Http\Controllers\PatientVisitController::statusRedirect() expects string,    
         mixed given.                                                                                                        
         🪪  argument.type                                                                                                   
  :411   Call to an undefined method object{value: string, label: string}::label().                                          
         🪪  method.notFound                                                                                                 
  :415   Method App\Http\Controllers\PatientVisitController::enumOptions() should return array<int, array{value: string, la  
         bel: string}> but returns list<array{value: string, label: mixed}>.                                                 
         🪪  return.type                                                                                                     
         💡  Offset 'label' (string) does not accept type mixed.                                                             
  :427   Using nullsafe method call on non-nullable type Illuminate\Database\Eloquent\Collection<int, App\Models\LabRequest  
         >. Use -> instead.                                                                                                  
         🪪  nullsafe.neverNull                                                                                              
  :428   Using nullsafe method call on non-nullable type Illuminate\Database\Eloquent\Collection<int, App\Models\LabRequest  
         Item>. Use -> instead.                                                                                              
         🪪  nullsafe.neverNull                                                                                              
 ------ -------------------------------------------------------------------------------------------------------------------- 

 ------ ------------------------------------------------------------------------------------------------------------------- 
  Line   app\Http\Controllers\PharmacyPosCartController.php                                                                 
 ------ ------------------------------------------------------------------------------------------------------------------- 
  :37    Parameter #2 $attributes of method App\Actions\AddItemToPharmacyPosCartAction::handle() expects                    
         array{inventory_item_id?: string, quantity?: float|int|string, unit_price?: float|int|string|null,                 
         discount_amount?: float|int|string|null, notes?: string|null}, array<string, mixed> given.                         
         🪪  argument.type                                                                                                  
  :52    Parameter #2 $attributes of method App\Actions\UpdatePharmacyPosCartItemAction::handle() expects array{quantity?:  
         float|int|string, unit_price?: float|int|string|null, discount_amount?: float|int|string|null, notes?:             
         string|null}, array<string, mixed> given.                                                                          
         🪪  argument.type                                                                                                  
 ------ ------------------------------------------------------------------------------------------------------------------- 

 ------ -------------------------------------------------------------------------------------------------------------------- 
  Line   app\Http\Controllers\PharmacyQueueController.php                                                                    
 ------ -------------------------------------------------------------------------------------------------------------------- 
  :57    Call to an undefined method Illuminate\Contracts\Pagination\LengthAwarePaginator<int, App\Models\Prescription>::th  
         rough().                                                                                                            
         🪪  method.notFound                                                                                                 
  :125   Method App\Http\Controllers\PharmacyQueueController::itemBalancesForLocations() should return                       
         Illuminate\Support\Collection<string, float> but returns Illuminate\Support\Collection<(int|string), mixed>.        
         🪪  return.type                                                                                                     
  :132   Cannot cast mixed to float.                                                                                         
         🪪  cast.double                                                                                                     
  :147   Parameter #1 $items of method App\Http\Controllers\PharmacyQueueController::resolveAvailabilitySummary() expects    
         Illuminate\Support\Collection<int, array<string, mixed>>, Illuminate\Support\Collection<int, array<string, mixed>>  
          given.                                                                                                             
         🪪  argument.type                                                                                                   
         💡  Template type TValue on class Illuminate\Support\Collection is not covariant. Learn more: https://phpstan.org/b  
         log/whats-up-with-template-covariant                                                                                
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
 ------ -------------------------------------------------------------------------------------------------------------------- 

 ------ -------------------------------------------------------------------------------------------------------------------- 
  Line   app\Http\Controllers\PurchaseOrderController.php                                                                    
 ------ -------------------------------------------------------------------------------------------------------------------- 
  :100   Parameter #1 $attributes of method App\Actions\CreatePurchaseOrder::handle() expects array{tenant_id?: string,      
         branch_id: string, supplier_id: string, order_date: string, expected_delivery_date?: string|null, notes?:           
         string|null, approved_by?: string|null, approved_at?: string|null, ...}, array<string, mixed> given.                
         🪪  argument.type                                                                                                   
  :100   Parameter #2 $items of method App\Actions\CreatePurchaseOrder::handle() expects list<array{inventory_item_id: stri  
         ng, quantity_ordered: float|int|string, unit_cost: float|int|string}>, array<int, array{inventory_item_id: string,  
          quantity_ordered: float, unit_cost: float}> given.                                                                 
         🪪  argument.type                                                                                                   
         💡  array<int, array{inventory_item_id: string, quantity_ordered: float, unit_cost: float}> might not be a list.    
 ------ -------------------------------------------------------------------------------------------------------------------- 

 ------ -------------------------------------------------------------------------------------------------------------------- 
  Line   app\Http\Controllers\StaffController.php                                                                            
 ------ -------------------------------------------------------------------------------------------------------------------- 
  :173   Parameter #1 $array of function array_unique expects an array of values castable to string, array<mixed, mixed> gi  
         ven.                                                                                                                
         🪪  argument.type                                                                                                   
 ------ -------------------------------------------------------------------------------------------------------------------- 

 ------ ----------------------------------------------------------------------- 
  Line   app\Http\Controllers\SubscriptionActivationController.php              
 ------ ----------------------------------------------------------------------- 
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
 ------ ----------------------------------------------------------------------- 

 ------ -------------------------------------------------------------------------------------------------------------------- 
  Line   app\Http\Controllers\UserController.php                                                                             
 ------ -------------------------------------------------------------------------------------------------------------------- 
  :94    Parameter #1 $attributes of method App\Actions\CreateUser::handle() expects array{roles?: list<string>}, array<str  
         ing, mixed> given.                                                                                                  
         🪪  argument.type                                                                                                   
         💡  Offset 'roles' (list<string>) does not accept type mixed: mixed is not a list.                                  
  :121   Parameter #2 $attributes of method App\Actions\UpdateUser::handle() expects array{email?: string, roles?: list<str  
         ing>}, array<string, mixed> given.                                                                                  
         🪪  argument.type                                                                                                   
         💡  Offset 'roles' (list<string>) does not accept type mixed: mixed is not a list.                                  
  :137   Parameter #1 $user of method App\Actions\DeleteUser::handle() expects App\Models\User, App\Models\User|null given.  
         🪪  argument.type                                                                                                   
 ------ -------------------------------------------------------------------------------------------------------------------- 

 ------ -------------------------------------------------------------------------------------------------------------------- 
  Line   app\Http\Controllers\UserProfileController.php                                                                      
 ------ -------------------------------------------------------------------------------------------------------------------- 
  :27    Parameter #2 $attributes of method App\Actions\UpdateUser::handle() expects array{email?: string, roles?: list<str  
         ing>}, array<string, mixed> given.                                                                                  
         🪪  argument.type                                                                                                   
         💡  Offset 'roles' (list<string>) does not accept type mixed: mixed is not a list.                                  
 ------ -------------------------------------------------------------------------------------------------------------------- 

 ------ -------------------------------------------------------------------------------------------------------------------- 
  Line   app\Http\Controllers\VisitOrderController.php                                                                       
 ------ -------------------------------------------------------------------------------------------------------------------- 
  :149   Result of || is always true.                                                                                        
         🪪  booleanOr.alwaysTrue                                                                                            
         💡  Because the type is coming from a PHPDoc, you can turn off this check by setting treatPhpDocTypesAsCertain: fal  
         se in your phpstan.neon.                                                                                            
         💡  If App\Http\Controllers\VisitOrderController::resolveStaffId() is impure, add @phpstan-impure PHPDoc tag above   
         its declaration. Learn more: https://phpstan.org/blog/remembering-and-forgetting-returned-values                    
  :149   Strict comparison using !== between App\Enums\LabRequestItemStatus and 'pending' will always evaluate to true.      
         🪪  notIdentical.alwaysTrue                                                                                         
         💡  Because the type is coming from a PHPDoc, you can turn off this check by setting treatPhpDocTypesAsCertain: fal  
         se in your phpstan.neon.                                                                                            
  :154   Unreachable statement - code above always terminates.                                                               
         🪪  deadCode.unreachable                                                                                            
 ------ -------------------------------------------------------------------------------------------------------------------- 

 ------ -------------------------------------------------------------------------------------------------------------------- 
  Line   app\Http\Controllers\VisitPaymentController.php                                                                     
 ------ -------------------------------------------------------------------------------------------------------------------- 
  :43    Parameter #2 $attributes of method App\Actions\RecordVisitPayment::handle() expects array<string, mixed>, mixed gi  
         ven.                                                                                                                
         🪪  argument.type                                                                                                   
 ------ -------------------------------------------------------------------------------------------------------------------- 

 ------ -------------------------------------------------------------------------------------------------------------------- 
  Line   app\Http\Controllers\WorkspaceRegistrationController.php                                                            
 ------ -------------------------------------------------------------------------------------------------------------------- 
  :58    Parameter #1 $attributes of method App\Actions\RegisterWorkspace::handle() expects array<string, mixed>, array giv  
         en.                                                                                                                 
         🪪  argument.type                                                                                                   
 ------ -------------------------------------------------------------------------------------------------------------------- 

 ------ -------------------------------------------------------------------------------------------------------------------- 
  Line   app\Http\Middleware\HandleInertiaRequests.php                                                                       
 ------ -------------------------------------------------------------------------------------------------------------------- 
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
  :83    Parameter #1 $relations of method Illuminate\Database\Eloquent\Builder<Illuminate\Database\Eloquent\Model>::with()  
          expects array<array|(Closure(Illuminate\Database\Eloquent\Relations\Relation<*, *, *>): mixed)|string>|string,     
         array{currentSubscription: Closure(Illuminate\Database\Eloquent\Relations\HasOne):                                  
         Illuminate\Database\Eloquent\Relations\HasOne} given.                                                               
         🪪  argument.type                                                                                                   
  :92    Parameter #1 $relations of method Illuminate\Database\Eloquent\Builder<Illuminate\Database\Eloquent\Model>::with()  
          expects array<array|(Closure(Illuminate\Database\Eloquent\Relations\Relation<*, *, *>): mixed)|string>|string,     
         array{subscriptionPackage: Closure(Illuminate\Database\Eloquent\Relations\BelongsTo):                               
         Illuminate\Database\Eloquent\Relations\BelongsTo} given.                                                            
         🪪  argument.type                                                                                                   
  :120   Parameter #1 $callback of method Illuminate\Support\Collection<(int|string),mixed>::mapWithKeys() expects           
         callable(mixed, int|string): array<string, true>, Closure(string): non-empty-array<string, true> given.             
         🪪  argument.type                                                                                                   
         💡  Type string of parameter #1 $permission of passed callable needs to be same or wider than parameter type mixed  
         of accepting callable.                                                                                              
 ------ -------------------------------------------------------------------------------------------------------------------- 

 ------ -------------------------------------------------------------------------------------------------------------------- 
  Line   app\Http\Requests\CorrectLabResultEntryRequest.php                                                                  
 ------ -------------------------------------------------------------------------------------------------------------------- 
  :65    Strict comparison using !== between App\Models\LabTestCatalog and null will always evaluate to true.                
         🪪  notIdentical.alwaysTrue                                                                                         
         💡  Because the type is coming from a PHPDoc, you can turn off this check by setting treatPhpDocTypesAsCertain: fal  
         se in your phpstan.neon.                                                                                            
  :94    Strict comparison using !== between App\Models\LabTestCatalog and null will always evaluate to true.                
         🪪  notIdentical.alwaysTrue                                                                                         
         💡  Because the type is coming from a PHPDoc, you can turn off this check by setting treatPhpDocTypesAsCertain: fal  
         se in your phpstan.neon.                                                                                            
 ------ -------------------------------------------------------------------------------------------------------------------- 

 ------ -------------------------------------------------------------------------------------------------------------------- 
  Line   app\Http\Requests\DispensePrescriptionRequest.php                                                                   
 ------ -------------------------------------------------------------------------------------------------------------------- 
  :63    PHPDoc tag @return has invalid value (array<int, callable(\\Illuminate\\Validation\\Validator): void>): Unexpected  
          token "(", expected '>' at offset 38 on line 2                                                                     
         🪪  phpDoc.parseError                                                                                               
  :65    Method App\Http\Requests\DispensePrescriptionRequest::after() return type has no value type specified in iterable   
         type array.                                                                                                         
         🪪  missingType.iterableValue                                                                                       
         💡  See: https://phpstan.org/blog/solving-phpstan-no-value-type-specified-in-iterable-type                          
  :70    Call to an undefined method (object|string)::getKey().                                                              
         🪪  method.notFound                                                                                                 
  :79    Call to function is_array() with list<array{prescription_item_id: string, dispensed_quantity: float|int|string,     
         external_pharmacy: bool, external_reason: string|null, notes: string|null, substitution_inventory_item_id:          
         string|null, allocations: list<array{inventory_batch_id: string, quantity: float|int|string}>}> will always evalua  
         te to true.                                                                                                         
         🪪  function.alreadyNarrowedType                                                                                    
  :121   Cannot access property $tenant_id on App\Models\PatientVisit|null.                                                  
         🪪  property.nonObject                                                                                              
  :121   Parameter #1 $tenantId of method App\Support\GeneralSettings\TenantGeneralSettings::boolean() expects string,       
         string|null given.                                                                                                  
         🪪  argument.type                                                                                                   
  :127   Call to function is_array() with array{prescription_item_id: string, dispensed_quantity: float|int|string,          
         external_pharmacy: bool, external_reason: string|null, notes: string|null, substitution_inventory_item_id:          
         string|null, allocations: list<array{inventory_batch_id: string, quantity: float|int|string}>} will always evaluat  
         e to true.                                                                                                          
         🪪  function.alreadyNarrowedType                                                                                    
         💡  Because the type is coming from a PHPDoc, you can turn off this check by setting treatPhpDocTypesAsCertain: fal  
         se in your phpstan.neon.                                                                                            
  :131   Offset 'prescription_item_id' on array{prescription_item_id: string, dispensed_quantity: float|int|string,          
         external_pharmacy: bool, external_reason: string|null, notes: string|null, substitution_inventory_item_id:          
         string|null, allocations: list<array{inventory_batch_id: string, quantity: float|int|string}>} on left side of ??   
         always exists and is not nullable.                                                                                  
         🪪  nullCoalesce.offset                                                                                             
  :132   Offset 'dispensed_quantity' on array{prescription_item_id: string, dispensed_quantity: float|int|string,            
         external_pharmacy: bool, external_reason: string|null, notes: string|null, substitution_inventory_item_id:          
         string|null, allocations: list<array{inventory_batch_id: string, quantity: float|int|string}>} on left side of ??   
         always exists and is not nullable.                                                                                  
         🪪  nullCoalesce.offset                                                                                             
  :133   Offset 'external_pharmacy' on array{prescription_item_id: string, dispensed_quantity: float|int|string,             
         external_pharmacy: bool, external_reason: string|null, notes: string|null, substitution_inventory_item_id:          
         string|null, allocations: list<array{inventory_batch_id: string, quantity: float|int|string}>} on left side of ??   
         always exists and is not nullable.                                                                                  
         🪪  nullCoalesce.offset                                                                                             
  :135   Call to function is_string() with string will always evaluate to true.                                              
         🪪  function.alreadyNarrowedType                                                                                    
         💡  Because the type is coming from a PHPDoc, you can turn off this check by setting treatPhpDocTypesAsCertain: fal  
         se in your phpstan.neon.                                                                                            
  :224   Call to function is_array() with list<array{prescription_item_id: string, dispensed_quantity: float|int|string,     
         external_pharmacy: bool, external_reason: string|null, notes: string|null, substitution_inventory_item_id:          
         string|null, allocations: list<array{inventory_batch_id: string, quantity: float|int|string}>}> will always evalua  
         te to true.                                                                                                         
         🪪  function.alreadyNarrowedType                                                                                    
  :231   Cannot access property $tenant_id on App\Models\PatientVisit|null.                                                  
         🪪  property.nonObject                                                                                              
  :231   Parameter #1 $tenantId of method App\Support\GeneralSettings\TenantGeneralSettings::boolean() expects string,       
         string|null given.                                                                                                  
         🪪  argument.type                                                                                                   
  :253   Call to function is_array() with array{prescription_item_id: string, dispensed_quantity: float|int|string,          
         external_pharmacy: bool, external_reason: string|null, notes: string|null, substitution_inventory_item_id:          
         string|null, allocations: list<array{inventory_batch_id: string, quantity: float|int|string}>} will always evaluat  
         e to true.                                                                                                          
         🪪  function.alreadyNarrowedType                                                                                    
         💡  Because the type is coming from a PHPDoc, you can turn off this check by setting treatPhpDocTypesAsCertain: fal  
         se in your phpstan.neon.                                                                                            
  :257   Offset 'prescription_item_id' on array{prescription_item_id: string, dispensed_quantity: float|int|string,          
         external_pharmacy: bool, external_reason: string|null, notes: string|null, substitution_inventory_item_id:          
         string|null, allocations: list<array{inventory_batch_id: string, quantity: float|int|string}>} on left side of ??   
         always exists and is not nullable.                                                                                  
         🪪  nullCoalesce.offset                                                                                             
  :258   Offset 'dispensed_quantity' on array{prescription_item_id: string, dispensed_quantity: float|int|string,            
         external_pharmacy: bool, external_reason: string|null, notes: string|null, substitution_inventory_item_id:          
         string|null, allocations: list<array{inventory_batch_id: string, quantity: float|int|string}>} on left side of ??   
         always exists and is not nullable.                                                                                  
         🪪  nullCoalesce.offset                                                                                             
  :259   Offset 'allocations' on array{prescription_item_id: string, dispensed_quantity: float|int|string,                   
         external_pharmacy: bool, external_reason: string|null, notes: string|null, substitution_inventory_item_id:          
         string|null, allocations: list<array{inventory_batch_id: string, quantity: float|int|string}>} on left side of ??   
         always exists and is not nullable.                                                                                  
         🪪  nullCoalesce.offset                                                                                             
  :261   Call to function is_string() with string will always evaluate to true.                                              
         🪪  function.alreadyNarrowedType                                                                                    
         💡  Because the type is coming from a PHPDoc, you can turn off this check by setting treatPhpDocTypesAsCertain: fal  
         se in your phpstan.neon.                                                                                            
  :279   Call to function is_array() with list<array{inventory_batch_id: string, quantity: float|int|string}> will always e  
         valuate to true.                                                                                                    
         🪪  function.alreadyNarrowedType                                                                                    
         💡  Because the type is coming from a PHPDoc, you can turn off this check by setting treatPhpDocTypesAsCertain: fal  
         se in your phpstan.neon.                                                                                            
  :291   Call to function is_array() with array{inventory_batch_id: string, quantity: float|int|string} will always          
         evaluate to true.                                                                                                   
         🪪  function.alreadyNarrowedType                                                                                    
         💡  Because the type is coming from a PHPDoc, you can turn off this check by setting treatPhpDocTypesAsCertain: fal  
         se in your phpstan.neon.                                                                                            
  :295   Offset 'inventory_batch_id' on array{inventory_batch_id: string, quantity: float|int|string} on left side of ??     
         always exists and is not nullable.                                                                                  
         🪪  nullCoalesce.offset                                                                                             
  :296   Offset 'quantity' on array{inventory_batch_id: string, quantity: float|int|string} on left side of ?? always        
         exists and is not nullable.                                                                                         
         🪪  nullCoalesce.offset                                                                                             
  :298   Call to function is_string() with string will always evaluate to true.                                              
         🪪  function.alreadyNarrowedType                                                                                    
         💡  Because the type is coming from a PHPDoc, you can turn off this check by setting treatPhpDocTypesAsCertain: fal  
         se in your phpstan.neon.                                                                                            
  :450   Method App\Http\Requests\DispensePrescriptionRequest::normalizedItems() should return                               
         list<array{prescription_item_id: string, dispensed_quantity: float|int|string, external_pharmacy: bool,             
         external_reason: string|null, notes: string|null, substitution_inventory_item_id: string|null, allocations: list<a  
         rray{inventory_batch_id: string, quantity: float|int|string}>}> but returns list<array{prescription_item_id: non-e  
         mpty-string, dispensed_quantity: mixed, external_pharmacy: bool, external_reason: string|null, notes: string|null,  
         substitution_inventory_item_id: string|null, allocations: list<array{inventory_batch_id: non-empty-string, quantit  
         y: float|int|numeric-string}>}>.                                                                                    
         🪪  return.type                                                                                                     
         💡  Offset 'dispensed_quantity' (float|int|string) does not accept type mixed.                                      
 ------ -------------------------------------------------------------------------------------------------------------------- 

 ------ -------------------------------------------------------------------------------------------------------------- 
  Line   app\Http\Requests\StoreConsultationFacilityServiceOrderRequest.php                                            
 ------ -------------------------------------------------------------------------------------------------------------- 
  :20    Method App\Http\Requests\StoreConsultationFacilityServiceOrderRequest::rules() return type has no value type  
         specified in iterable type array.                                                                             
         🪪  missingType.iterableValue                                                                                 
         💡  See: https://phpstan.org/blog/solving-phpstan-no-value-type-specified-in-iterable-type                    
 ------ -------------------------------------------------------------------------------------------------------------- 

 ------ -------------------------------------------------------------------------------------------------------------- 
  Line   app\Http\Requests\StoreConsultationImagingRequest.php                                                         
 ------ -------------------------------------------------------------------------------------------------------------- 
  :22    Method App\Http\Requests\StoreConsultationImagingRequest::rules() return type has no value type specified in  
         iterable type array.                                                                                          
         🪪  missingType.iterableValue                                                                                 
         💡  See: https://phpstan.org/blog/solving-phpstan-no-value-type-specified-in-iterable-type                    
 ------ -------------------------------------------------------------------------------------------------------------- 

 ------ ------------------------------------------------------------------------------------------------------------------- 
  Line   app\Http\Requests\StoreConsultationLabRequest.php                                                                  
 ------ ------------------------------------------------------------------------------------------------------------------- 
  :20    Method App\Http\Requests\StoreConsultationLabRequest::rules() return type has no value type specified in iterable  
         type array.                                                                                                        
         🪪  missingType.iterableValue                                                                                      
         💡  See: https://phpstan.org/blog/solving-phpstan-no-value-type-specified-in-iterable-type                         
 ------ ------------------------------------------------------------------------------------------------------------------- 

 ------ ------------------------------------------------------------------------------------------------------------------- 
  Line   app\Http\Requests\StoreConsultationPrescriptionRequest.php                                                         
 ------ ------------------------------------------------------------------------------------------------------------------- 
  :58    Offset 'is_prn' on array{inventory_item_id: string, dosage: string, frequency: string, route: string,              
         duration_days: int, quantity: int, instructions: string|null, is_prn: bool, ...} on left side of ?? always exists  
         and is not nullable.                                                                                               
         🪪  nullCoalesce.offset                                                                                            
 ------ ------------------------------------------------------------------------------------------------------------------- 

 ------ --------------------------------------------------------------------------------------------------------------- 
  Line   app\Http\Requests\StoreDispenseRequest.php                                                                     
 ------ --------------------------------------------------------------------------------------------------------------- 
  :65    Call to an undefined method (object|string)::getKey().                                                         
         🪪  method.notFound                                                                                            
  :124   Parameter #1 $tenantId of method App\Support\GeneralSettings\TenantGeneralSettings::boolean() expects string,  
         string|null given.                                                                                             
         🪪  argument.type                                                                                              
 ------ --------------------------------------------------------------------------------------------------------------- 

 ------ ---------------------------------------------------- 
  Line   app\Http\Requests\StoreInsurancePackageRequest.php  
 ------ ---------------------------------------------------- 
  :44    Cannot cast mixed to string.                        
         🪪  cast.string                                     
 ------ ---------------------------------------------------- 

 ------ ------------------------------------------------------------------------------------------------------------------- 
  Line   app\Http\Requests\StoreInventoryItemRequest.php                                                                    
 ------ ------------------------------------------------------------------------------------------------------------------- 
  :111   Method App\Http\Requests\StoreInventoryItemRequest::numericOrDefault() should return float|int|string but returns  
         mixed.                                                                                                             
         🪪  return.type                                                                                                    
 ------ ------------------------------------------------------------------------------------------------------------------- 

 ------ -------------------------------------------------------------------------------------------------------------------- 
  Line   app\Http\Requests\StoreLabResultEntryRequest.php                                                                    
 ------ -------------------------------------------------------------------------------------------------------------------- 
  :50    Cannot cast mixed to string.                                                                                        
         🪪  cast.string                                                                                                     
  :58    Cannot cast mixed to string.                                                                                        
         🪪  cast.string                                                                                                     
  :59    Expression on left side of ?? is not nullable.                                                                      
         🪪  nullCoalesce.expr                                                                                               
  :59    Using nullsafe method call on non-nullable type Illuminate\Database\Eloquent\Collection<int, App\Models\LabTestRes  
         ultOption>. Use -> instead.                                                                                         
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
 ------ -------------------------------------------------------------------------------------------------------------------- 

 ------ -------------------------------------------------------------------------------------------------------------------- 
  Line   app\Http\Requests\StoreLabTestCatalogRequest.php                                                                    
 ------ -------------------------------------------------------------------------------------------------------------------- 
  :98    Parameter #1 $value of function collect expects Illuminate\Contracts\Support\Arrayable<(int|string),                
         mixed>|iterable<(int|string), mixed>|null, mixed given.                                                             
         🪪  argument.type                                                                                                   
  :98    Unable to resolve the template type TKey in call to function collect                                                
         🪪  argument.templateType                                                                                           
         💡  See: https://phpstan.org/blog/solving-phpstan-error-unable-to-resolve-template-type                             
  :98    Unable to resolve the template type TValue in call to function collect                                              
         🪪  argument.templateType                                                                                           
         💡  See: https://phpstan.org/blog/solving-phpstan-error-unable-to-resolve-template-type                             
  :117   Method App\Http\Requests\StoreLabTestCatalogRequest::selectedResultTypeCode() should return string|null but         
         returns mixed.                                                                                                      
         🪪  return.type                                                                                                     
  :127   Method App\Http\Requests\StoreLabTestCatalogRequest::filledResultOptions() should return array<int, array<string,   
         mixed>> but returns array<int, array<mixed, mixed>>.                                                                
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
  :139   Method App\Http\Requests\StoreLabTestCatalogRequest::filledResultParameters() should return array<int, array<strin  
         g, mixed>> but returns array<int, array<mixed, mixed>>.                                                             
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
 ------ -------------------------------------------------------------------------------------------------------------------- 

 ------ ------------------------------------------- 
  Line   app\Http\Requests\StorePatientRequest.php  
 ------ ------------------------------------------- 
  :82    Cannot cast mixed to string.               
         🪪  cast.string                            
 ------ ------------------------------------------- 

 ------ ----------------------------------------------------------------------------------------------------- 
  Line   app\Http\Requests\UpdateAppointmentCategoryRequest.php                                               
 ------ ----------------------------------------------------------------------------------------------------- 
  :27    Using nullsafe property access on non-nullable type App\Models\AppointmentCategory. Use -> instead.  
         🪪  nullsafe.neverNull                                                                               
 ------ ----------------------------------------------------------------------------------------------------- 

 ------ ------------------------------------------------------------------------------------------------- 
  Line   app\Http\Requests\UpdateAppointmentModeRequest.php                                               
 ------ ------------------------------------------------------------------------------------------------- 
  :27    Using nullsafe property access on non-nullable type App\Models\AppointmentMode. Use -> instead.  
         🪪  nullsafe.neverNull                                                                           
 ------ ------------------------------------------------------------------------------------------------- 

 ------ ------------------------------------------------- 
  Line   app\Http\Requests\UpdateConsultationRequest.php  
 ------ ------------------------------------------------- 
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
 ------ ------------------------------------------------- 

 ------ ----------------------------------------------------- 
  Line   app\Http\Requests\UpdateInsurancePackageRequest.php  
 ------ ----------------------------------------------------- 
  :47    Cannot cast mixed to string.                         
         🪪  cast.string                                      
 ------ ----------------------------------------------------- 

 ------ -------------------------------------------------------------------------------------------------------------------- 
  Line   app\Http\Requests\UpdateInventoryItemRequest.php                                                                    
 ------ -------------------------------------------------------------------------------------------------------------------- 
  :111   Method App\Http\Requests\UpdateInventoryItemRequest::numericOrDefault() should return float|int|string but returns  
         mixed.                                                                                                              
         🪪  return.type                                                                                                     
 ------ -------------------------------------------------------------------------------------------------------------------- 

 ------ -------------------------------------------------------------------------------------------------------------------- 
  Line   app\Http\Requests\UpdateLabTestCatalogRequest.php                                                                   
 ------ -------------------------------------------------------------------------------------------------------------------- 
  :101   Parameter #1 $value of function collect expects Illuminate\Contracts\Support\Arrayable<(int|string),                
         mixed>|iterable<(int|string), mixed>|null, mixed given.                                                             
         🪪  argument.type                                                                                                   
  :101   Unable to resolve the template type TKey in call to function collect                                                
         🪪  argument.templateType                                                                                           
         💡  See: https://phpstan.org/blog/solving-phpstan-error-unable-to-resolve-template-type                             
  :101   Unable to resolve the template type TValue in call to function collect                                              
         🪪  argument.templateType                                                                                           
         💡  See: https://phpstan.org/blog/solving-phpstan-error-unable-to-resolve-template-type                             
  :120   Method App\Http\Requests\UpdateLabTestCatalogRequest::selectedResultTypeCode() should return string|null but        
         returns mixed.                                                                                                      
         🪪  return.type                                                                                                     
  :130   Method App\Http\Requests\UpdateLabTestCatalogRequest::filledResultOptions() should return array<int, array<string,  
          mixed>> but returns array<int, array<mixed, mixed>>.                                                               
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
  :142   Method App\Http\Requests\UpdateLabTestCatalogRequest::filledResultParameters() should return array<int, array<stri  
         ng, mixed>> but returns array<int, array<mixed, mixed>>.                                                            
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
 ------ -------------------------------------------------------------------------------------------------------------------- 

 ------ --------------------------------------------------------------------------------------- 
  Line   app\Http\Requests\UpdateStaffRequest.php                                               
 ------ --------------------------------------------------------------------------------------- 
  :24    Access to an undefined property (object|string|null)::$id.                             
         🪪  property.notFound                                                                  
         💡  Learn more: https://phpstan.org/blog/solving-phpstan-access-to-undefined-property  
  :30    Binary operation "." between 'unique:staff,email,' and mixed results in an error.      
         🪪  binaryOp.invalid                                                                   
 ------ --------------------------------------------------------------------------------------- 

 ------ --------------------------------------------------------------------------------------- 
  Line   app\Http\Requests\UpdateUnitRequest.php                                                
 ------ --------------------------------------------------------------------------------------- 
  :30    Access to an undefined property (object|string|null)::$id.                             
         🪪  property.notFound                                                                  
         💡  Learn more: https://phpstan.org/blog/solving-phpstan-access-to-undefined-property  
  :36    Access to an undefined property (object|string|null)::$id.                             
         🪪  property.notFound                                                                  
         💡  Learn more: https://phpstan.org/blog/solving-phpstan-access-to-undefined-property  
 ------ --------------------------------------------------------------------------------------- 

 ------ -------------------------------------------------------------------------------------------------------------------- 
  Line   app\Models\Allergen.php                                                                                             
 ------ -------------------------------------------------------------------------------------------------------------------- 
  :31    PHPDoc tag @use has invalid type Database\Factories\AllergenFactory.                                                
         🪪  class.notFound                                                                                                  
  :31    Type Database\Factories\AllergenFactory in generic type Illuminate\Database\Eloquent\Factories\HasFactory<Database  
         \Factories\AllergenFactory> in PHPDoc tag @use is not subtype of template type TFactory of Illuminate\Database\Elo  
         quent\Factories\Factory of trait Illuminate\Database\Eloquent\Factories\HasFactory.                                 
         🪪  generics.notSubtype                                                                                             
 ------ -------------------------------------------------------------------------------------------------------------------- 

 ------ -------------------------------------------------------------------------------------------------------------------- 
  Line   app\Models\Department.php                                                                                           
 ------ -------------------------------------------------------------------------------------------------------------------- 
  :20    PHPDoc tag @use has invalid type Database\Factories\DepartmentFactory.                                              
         🪪  class.notFound                                                                                                  
  :20    Type Database\Factories\DepartmentFactory in generic type Illuminate\Database\Eloquent\Factories\HasFactory<Databa  
         se\Factories\DepartmentFactory> in PHPDoc tag @use is not subtype of template type TFactory of Illuminate\Database  
         \Eloquent\Factories\Factory of trait Illuminate\Database\Eloquent\Factories\HasFactory.                             
         🪪  generics.notSubtype                                                                                             
 ------ -------------------------------------------------------------------------------------------------------------------- 

 ------ ----------------------------------------------------------------------------------- 
  Line   database\seeders\SupportUserSeeder.php                                             
 ------ ----------------------------------------------------------------------------------- 
  :52    Cannot call method orderBy() on mixed.                                             
         🪪  method.nonObject                                                               
  :52    Cannot call method orderByDesc() on mixed.                                         
         🪪  method.nonObject                                                               
  :53    Cannot call method orderBy() on mixed.                                             
         🪪  method.nonObject                                                               
  :119   Parameter #1 $string of function mb_strtoupper expects string, string|null given.  
         🪪  argument.type                                                                  
 ------ ----------------------------------------------------------------------------------- 

                                                                                                                        
 [ERROR] Found 432 errors                                                                                               
                                                                                                                        