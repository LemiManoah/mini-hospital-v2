                                                                                        
  :43    Method App\Models\ReconciliationItem::inventoryBatch() return type with generic class                               
         Illuminate\Database\Eloquent\Relations\BelongsTo does not specify its types: TRelatedModel, TDeclaringModel         
         🪪  missingType.generics                                                                                            

  Line   app\Models\Role.php                                                                                              
  :13    Class App\Models\Role uses generic trait Illuminate\Database\Eloquent\Factories\HasFactory but does not specify  
         its types: TFactory                                                                                              
         🪪  missingType.generics                                                                                         

  Line   app\Models\SpecimenType.php                                                                                         
 
  :18    PHPDoc tag @use has invalid type Database\Factories\SpecimenTypeFactory.                                            
         🪪  class.notFound                                                                                                  
  :18    Type Database\Factories\SpecimenTypeFactory in generic type Illuminate\Database\Eloquent\Factories\HasFactory<Data  
         base\Factories\SpecimenTypeFactory> in PHPDoc tag @use is not subtype of template type TFactory of Illuminate\Data  
         base\Eloquent\Factories\Factory of trait Illuminate\Database\Eloquent\Factories\HasFactory.                         
         🪪  generics.notSubtype                                                                                             
  :27    Method App\Models\SpecimenType::labTests() return type with generic class                                           
         Illuminate\Database\Eloquent\Relations\BelongsToMany does not specify its types: TRelatedModel, TDeclaringModel,    
         TPivotModel, TAccessor (2-4 required)                                                                               
         🪪  missingType.generics                                                                                            

  Line   app\Models\StaffPosition.php                                                                                        

  :19    PHPDoc tag @use has invalid type Database\Factories\StaffPositionFactory.                                           
         🪪  class.notFound                                                                                                  
  :19    Type Database\Factories\StaffPositionFactory in generic type Illuminate\Database\Eloquent\Factories\HasFactory<Dat  
         abase\Factories\StaffPositionFactory> in PHPDoc tag @use is not subtype of template type TFactory of Illuminate\Da  
         tabase\Eloquent\Factories\Factory of trait Illuminate\Database\Eloquent\Factories\HasFactory.                       
         🪪  generics.notSubtype                                                                                             

  Line   app\Models\Supplier.php                                                                                            
--
  :34    Method App\Models\Supplier::purchaseOrders() return type with generic class                                        
         Illuminate\Database\Eloquent\Relations\HasMany does not specify its types: TRelatedModel, TDeclaringModel          
         🪪  missingType.generics                                                                                           
  :39    Method App\Models\Supplier::active() has parameter $query with generic class Illuminate\Database\Eloquent\Builder  
         but does not specify its types: TModel                                                                             
         🪪  missingType.generics                                                                                           
  :39    Method App\Models\Supplier::active() return type with generic class Illuminate\Database\Eloquent\Builder does not  
         specify its types: TModel                                                                                          
         🪪  missingType.generics                                                                                           

  Line   app\Models\TenantGeneralSetting.php                                                                             
--
  :14    Class App\Models\TenantGeneralSetting uses generic trait Illuminate\Database\Eloquent\Factories\HasFactory but  
         does not specify its types: TFactory                                                                            
         🪪  missingType.generics                                                                                        
 
  Line   app\Models\TenantSubscription.php                                                                                   

  :16    PHPDoc tag @use has invalid type Database\Factories\TenantSubscriptionFactory.                                      
         🪪  class.notFound                                                                                                  
  :16    Type Database\Factories\TenantSubscriptionFactory in generic type                                                   
         Illuminate\Database\Eloquent\Factories\HasFactory<Database\Factories\TenantSubscriptionFactory> in PHPDoc tag @use  
          is not subtype of template type TFactory of Illuminate\Database\Eloquent\Factories\Factory of trait                
         Illuminate\Database\Eloquent\Factories\HasFactory.                                                                  
         🪪  generics.notSubtype                                                                                             

  Line   app\Models\TenantSupportNote.php                                                                                    
 
  :15    PHPDoc tag @use has invalid type Database\Factories\TenantSupportNoteFactory.                                       
         🪪  class.notFound                                                                                                  
  :15    Type Database\Factories\TenantSupportNoteFactory in generic type Illuminate\Database\Eloquent\Factories\HasFactory  
         <Database\Factories\TenantSupportNoteFactory> in PHPDoc tag @use is not subtype of template type TFactory of Illum  
         inate\Database\Eloquent\Factories\Factory of trait Illuminate\Database\Eloquent\Factories\HasFactory.               
         🪪  generics.notSubtype                                                                                             

  Line   app\Models\TriageRecord.php                                                                                         

  :23    PHPDoc tag @use has invalid type Database\Factories\TriageRecordFactory.                                            
         🪪  class.notFound                                                                                                  
  :23    Type Database\Factories\TriageRecordFactory in generic type Illuminate\Database\Eloquent\Factories\HasFactory<Data  
         base\Factories\TriageRecordFactory> in PHPDoc tag @use is not subtype of template type TFactory of Illuminate\Data  
         base\Eloquent\Factories\Factory of trait Illuminate\Database\Eloquent\Factories\HasFactory.                         
         🪪  generics.notSubtype                                                                                             
  :44    Method App\Models\TriageRecord::visit() return type with generic class                                              
         Illuminate\Database\Eloquent\Relations\BelongsTo does not specify its types: TRelatedModel, TDeclaringModel         
         🪪  missingType.generics                                                                                            
  :49    Method App\Models\TriageRecord::nurse() return type with generic class                                              
         Illuminate\Database\Eloquent\Relations\BelongsTo does not specify its types: TRelatedModel, TDeclaringModel         
         🪪  missingType.generics                                                                                            
  :54    Method App\Models\TriageRecord::assignedClinic() return type with generic class                                     
         Illuminate\Database\Eloquent\Relations\BelongsTo does not specify its types: TRelatedModel, TDeclaringModel         
         🪪  missingType.generics                                                                                            
  :59    Method App\Models\TriageRecord::vitalSigns() return type with generic class                                         
         Illuminate\Database\Eloquent\Relations\HasMany does not specify its types: TRelatedModel, TDeclaringModel           
         🪪  missingType.generics                                                                                            

  Line   app\Models\Unit.php                                                                                                 

  :32    PHPDoc tag @use has invalid type Database\Factories\UnitFactory.                                                    
         🪪  class.notFound                                                                                                  
  :32    Type Database\Factories\UnitFactory in generic type Illuminate\Database\Eloquent\Factories\HasFactory<Database\Fac  
         tories\UnitFactory> in PHPDoc tag @use is not subtype of template type TFactory of Illuminate\Database\Eloquent\Fa  
         ctories\Factory of trait Illuminate\Database\Eloquent\Factories\HasFactory.                                         
         🪪  generics.notSubtype                                                                                             

  Line   app\Models\User.php                                                                                                 

  :37    PHPDoc tag @use has invalid type App\Models\UserFactory.                                                            
         🪪  class.notFound                                                                                                  
  :37    Type App\Models\UserFactory in generic type Illuminate\Database\Eloquent\Factories\HasFactory<App\Models\UserFacto  
         ry> in PHPDoc tag @use is not subtype of template type TFactory of Illuminate\Database\Eloquent\Factories\Factory   
         of trait Illuminate\Database\Eloquent\Factories\HasFactory.                                                         
         🪪  generics.notSubtype                                                                                             
  :116   Method App\Models\User::name() return type with generic class Illuminate\Database\Eloquent\Casts\Attribute does     
         not specify its types: TGet, TSet                                                                                   
         🪪  missingType.generics                                                                                            
  :130   Method App\Models\User::avatar() return type with generic class Illuminate\Database\Eloquent\Casts\Attribute does   
         not specify its types: TGet, TSet                                                                                   
         🪪  missingType.generics                                                                                            

  Line   app\Models\VisitBilling.php                                                                                         
 
  :22    PHPDoc tag @use has invalid type Database\Factories\VisitBillingFactory.                                            
         🪪  class.notFound                                                                                                  
  :22    Type Database\Factories\VisitBillingFactory in generic type Illuminate\Database\Eloquent\Factories\HasFactory<Data  
         base\Factories\VisitBillingFactory> in PHPDoc tag @use is not subtype of template type TFactory of Illuminate\Data  
         base\Eloquent\Factories\Factory of trait Illuminate\Database\Eloquent\Factories\HasFactory.                         
         🪪  generics.notSubtype                                                                                             
  :44    Method App\Models\VisitBilling::visit() return type with generic class                                              
         Illuminate\Database\Eloquent\Relations\BelongsTo does not specify its types: TRelatedModel, TDeclaringModel         
         🪪  missingType.generics                                                                                            
  :49    Method App\Models\VisitBilling::visitPayer() return type with generic class                                         
         Illuminate\Database\Eloquent\Relations\BelongsTo does not specify its types: TRelatedModel, TDeclaringModel         
         🪪  missingType.generics                                                                                            
  :54    Method App\Models\VisitBilling::branch() return type with generic class                                             
         Illuminate\Database\Eloquent\Relations\BelongsTo does not specify its types: TRelatedModel, TDeclaringModel         
         🪪  missingType.generics                                                                                            
  :59    Method App\Models\VisitBilling::insuranceCompany() return type with generic class                                   
         Illuminate\Database\Eloquent\Relations\BelongsTo does not specify its types: TRelatedModel, TDeclaringModel         
         🪪  missingType.generics                                                                                            
  :64    Method App\Models\VisitBilling::insurancePackage() return type with generic class                                   
         Illuminate\Database\Eloquent\Relations\BelongsTo does not specify its types: TRelatedModel, TDeclaringModel         
         🪪  missingType.generics                                                                                            
  :69    Method App\Models\VisitBilling::charges() return type with generic class                                            
         Illuminate\Database\Eloquent\Relations\HasMany does not specify its types: TRelatedModel, TDeclaringModel           
         🪪  missingType.generics                                                                                            
  :74    Method App\Models\VisitBilling::payments() return type with generic class                                           
         Illuminate\Database\Eloquent\Relations\HasMany does not specify its types: TRelatedModel, TDeclaringModel           
         🪪  missingType.generics                                                                                            

  Line   app\Models\VisitCharge.php                                                                                          

  :21    PHPDoc tag @use has invalid type Database\Factories\VisitChargeFactory.                                             
         🪪  class.notFound                                                                                                  
  :21    Type Database\Factories\VisitChargeFactory in generic type Illuminate\Database\Eloquent\Factories\HasFactory<Datab  
         ase\Factories\VisitChargeFactory> in PHPDoc tag @use is not subtype of template type TFactory of Illuminate\Databa  
         se\Eloquent\Factories\Factory of trait Illuminate\Database\Eloquent\Factories\HasFactory.                           
         🪪  generics.notSubtype                                                                                             
  :38    Method App\Models\VisitCharge::billing() return type with generic class                                             
         Illuminate\Database\Eloquent\Relations\BelongsTo does not specify its types: TRelatedModel, TDeclaringModel         
         🪪  missingType.generics                                                                                            
  :43    Method App\Models\VisitCharge::visit() return type with generic class                                               
         Illuminate\Database\Eloquent\Relations\BelongsTo does not specify its types: TRelatedModel, TDeclaringModel         
         🪪  missingType.generics                                                                                            
  :48    Method App\Models\VisitCharge::branch() return type with generic class                                              
         Illuminate\Database\Eloquent\Relations\BelongsTo does not specify its types: TRelatedModel, TDeclaringModel         
         🪪  missingType.generics                                                                                            
  :53    Method App\Models\VisitCharge::source() return type with generic class                                              
         Illuminate\Database\Eloquent\Relations\MorphTo does not specify its types: TRelatedModel, TDeclaringModel           
         🪪  missingType.generics                                                                                            



  Line   app\Models\VisitPayer.php                                                                                           

  :21    PHPDoc tag @use has invalid type Database\Factories\VisitPayerFactory.                                              
         🪪  class.notFound                                                                                                  
  :21    Type Database\Factories\VisitPayerFactory in generic type Illuminate\Database\Eloquent\Factories\HasFactory<Databa  
         se\Factories\VisitPayerFactory> in PHPDoc tag @use is not subtype of template type TFactory of Illuminate\Database  
         \Eloquent\Factories\Factory of trait Illuminate\Database\Eloquent\Factories\HasFactory.                             
         🪪  generics.notSubtype                                                                                             
  :34    Method App\Models\VisitPayer::visit() return type with generic class                                                
         Illuminate\Database\Eloquent\Relations\BelongsTo does not specify its types: TRelatedModel, TDeclaringModel         
         🪪  missingType.generics                                                                                            
  :39    Method App\Models\VisitPayer::insuranceCompany() return type with generic class                                     
         Illuminate\Database\Eloquent\Relations\BelongsTo does not specify its types: TRelatedModel, TDeclaringModel         
         🪪  missingType.generics                                                                                            
  :44    Method App\Models\VisitPayer::insurancePackage() return type with generic class                                     
         Illuminate\Database\Eloquent\Relations\BelongsTo does not specify its types: TRelatedModel, TDeclaringModel         
         🪪  missingType.generics                                                                                            
  :49    Method App\Models\VisitPayer::billing() return type with generic class                                              
         Illuminate\Database\Eloquent\Relations\HasOne does not specify its types: TRelatedModel, TDeclaringModel            
         🪪  missingType.generics                                                                                            



  Line   app\Models\VitalSign.php                                                                                            

  :15    PHPDoc tag @use has invalid type Database\Factories\VitalSignFactory.                                               
         🪪  class.notFound                                                                                                  
  :15    Type Database\Factories\VitalSignFactory in generic type Illuminate\Database\Eloquent\Factories\HasFactory<Databas  
         e\Factories\VitalSignFactory> in PHPDoc tag @use is not subtype of template type TFactory of Illuminate\Database\E  
         loquent\Factories\Factory of trait Illuminate\Database\Eloquent\Factories\HasFactory.                               
         🪪  generics.notSubtype                                                                                             
  :36    Method App\Models\VitalSign::triage() return type with generic class                                                
         Illuminate\Database\Eloquent\Relations\BelongsTo does not specify its types: TRelatedModel, TDeclaringModel         
         🪪  missingType.generics                                                                                            
  :41    Method App\Models\VitalSign::recordedBy() return type with generic class                                            
         Illuminate\Database\Eloquent\Relations\BelongsTo does not specify its types: TRelatedModel, TDeclaringModel         
         🪪  missingType.generics                                                                                            



  Line   app\Rules\NoOverlappingInsurancePriceWindow.php  

  :41    Cannot access property $ignoreId on mixed.       
         🪪  property.nonObject                           
  :41    Undefined variable: $this                        
         🪪  variable.undefined                           

 
  Line   app\Support\DoctorConsultationAccess.php                      
  :38    Cannot access property $value on App\Enums\VisitStatus|null.  
         🪪  property.nonObject                                        
  :38    Cannot access property $value on App\Enums\VisitStatus|null.  
         🪪  property.nonObject                                        


  Line   app\Support\GeneralSettings\TenantGeneralSettings.php                                                               

  :21    Parameter #1 $stored of static method App\Support\GeneralSettings\GeneralSettingsRegistry::resolveValues() expects  
         array<string, string|null>, array<mixed> given.                                                                     
         🪪  argument.type                                                                                                   



  Line   app\Support\InventoryLocationAccess.php                                                                             

  :18    Method App\Support\InventoryLocationAccess::accessibleLocations() has parameter $requestedTypes with no value type  
         specified in iterable type array.                                                                                   
         🪪  missingType.iterableValue                                                                                       
         💡  See: https://phpstan.org/blog/solving-phpstan-no-value-type-specified-in-iterable-type                          
  :23    Method App\Support\InventoryLocationAccess::accessibleLocations() should return Illuminate\Support\Collection<int,  
          App\Models\InventoryLocation> but returns Illuminate\Support\Collection<(int|string), mixed>.                      
         🪪  return.type                                                                                                     
  :31    Parameter #1 $types of method App\Support\InventoryLocationAccess::normalizeTypes() expects array<int, App\Enums\I  
         nventoryLocationType|string>, array given.                                                                          
         🪪  argument.type                                                                                                   
  :53    Method App\Support\InventoryLocationAccess::accessibleLocationIds() has parameter $requestedTypes with no value     
         type specified in iterable type array.                                                                              
         🪪  missingType.iterableValue                                                                                       
         💡  See: https://phpstan.org/blog/solving-phpstan-no-value-type-specified-in-iterable-type                          
  :55    Method App\Support\InventoryLocationAccess::accessibleLocationIds() should return list<string> but returns array<i  
         nt, non-empty-string>.                                                                                              
         🪪  return.type                                                                                                     
         💡  array<int, non-empty-string> might not be a list.                                                               
  :65    Method App\Support\InventoryLocationAccess::requisitionFulfillingLocations() has parameter $requestedTypes with no  
         value type specified in iterable type array.                                                                        
         🪪  missingType.iterableValue                                                                                       
         💡  See: https://phpstan.org/blog/solving-phpstan-no-value-type-specified-in-iterable-type                          
  :70    Method App\Support\InventoryLocationAccess::requisitionFulfillingLocations() should return                          
         Illuminate\Support\Collection<int, App\Models\InventoryLocation> but returns Illuminate\Support\Collection<(int|st  
         ring), mixed>.                                                                                                      
         🪪  return.type                                                                                                     
  :78    Parameter #1 $types of method App\Support\InventoryLocationAccess::normalizeTypes() expects array<int, App\Enums\I  
         nventoryLocationType|string>, array given.                                                                          
         🪪  argument.type                                                                                                   
  :92    Method App\Support\InventoryLocationAccess::requisitionFulfillingLocationIds() has parameter $requestedTypes with   
         no value type specified in iterable type array.                                                                     
         🪪  missingType.iterableValue                                                                                       
         💡  See: https://phpstan.org/blog/solving-phpstan-no-value-type-specified-in-iterable-type                          
  :94    Method App\Support\InventoryLocationAccess::requisitionFulfillingLocationIds() should return list<string> but retu  
         rns array<int, non-empty-string>.                                                                                   
         🪪  return.type                                                                                                     
         💡  array<int, non-empty-string> might not be a list.                                                               
  :108   Method App\Support\InventoryLocationAccess::canAccessLocationForTypes() has parameter $allowedTypes with no value   
         type specified in iterable type array.                                                                              
         🪪  missingType.iterableValue                                                                                       
         💡  See: https://phpstan.org/blog/solving-phpstan-no-value-type-specified-in-iterable-type                          
  :115   Method App\Support\InventoryLocationAccess::canCreateRequestedRequisition() has parameter $requestingTypes with no  
         value type specified in iterable type array.                                                                        
         🪪  missingType.iterableValue                                                                                       
         💡  See: https://phpstan.org/blog/solving-phpstan-no-value-type-specified-in-iterable-type                          
  :189   Method App\Support\InventoryLocationAccess::restrictedTypes() should return list<string> but returns array<int, mi  
         xed>.                                                                                                               
         🪪  return.type                                                                                                     
         💡  array<int, mixed> might not be a list.                                                                          
  :201   Method App\Support\InventoryLocationAccess::normalizeTypes() should return list<string> but returns array<int, non  
         -empty-string>.                                                                                                     
         🪪  return.type                                                                                                     
         💡  array<int, non-empty-string> might not be a list.                                                               



  Line   app\Support\InventoryRequisitionWorkflow.php                                                                        

  :44    Method App\Support\InventoryRequisitionWorkflow::applyIncomingQueueScope() has parameter $fulfillingLocationIds     
         with no value type specified in iterable type array.                                                                
         🪪  missingType.iterableValue                                                                                       
         💡  See: https://phpstan.org/blog/solving-phpstan-no-value-type-specified-in-iterable-type                          
  :44    Method App\Support\InventoryRequisitionWorkflow::applyIncomingQueueScope() has parameter $query with generic class  
         Illuminate\Database\Eloquent\Builder but does not specify its types: TModel                                         
         🪪  missingType.generics                                                                                            
  :57    Using nullsafe property access on non-nullable type App\Enums\InventoryRequisitionStatus. Use -> instead.           
         🪪  nullsafe.neverNull                                                                                              


  Line   app\Support\InventoryStockLedger.php  
  :28    Cannot cast mixed to string.          
         🪪  cast.string                       
  :29    Cannot cast mixed to string.          
         🪪  cast.string                       
  :30    Cannot cast mixed to float.           
         🪪  cast.double                       
  :68    Cannot cast mixed to string.          
         🪪  cast.string                       
  :69    Cannot cast mixed to string.          
         🪪  cast.string                       
  :70    Cannot cast mixed to string.          
         🪪  cast.string                       
  :71    Cannot cast mixed to string.          
         🪪  cast.string                       
  :72    Cannot cast mixed to string.          
         🪪  cast.string                       
  :73    Cannot cast mixed to float.           
         🪪  cast.double                       


  Line   app\Support\PrescriptionDispenseProgress.php                                                                        

  :40    Method App\Support\PrescriptionDispenseProgress::postedLineSummaries() should return Illuminate\Support\Collection  
         <string, array{dispensed_quantity: float, external_quantity: float, covered_quantity: float, latest_dispensed_at:   
         Illuminate\Support\Carbon|null, external_pharmacy: bool}> but returns Illuminate\Support\Collection<string, array{  
         dispensed_quantity: float, external_quantity: float, covered_quantity: float, latest_dispensed_at:                  
         Carbon\CarbonImmutable|null, external_pharmacy: bool}>.                                                             
         🪪  return.type                                                                                                     
  :46    Access to an undefined property App\Models\DispensingRecordItem::$external_quantity.                                
         🪪  property.notFound                                                                                               
         💡  Learn more: https://phpstan.org/blog/solving-phpstan-access-to-undefined-property                               
  :46    Cannot cast mixed to float.                                                                                         
         🪪  cast.double                                                                                                     
  :48    Access to an undefined property App\Models\DispensingRecordItem::$external_quantity.                                
         🪪  property.notFound                                                                                               
         💡  Learn more: https://phpstan.org/blog/solving-phpstan-access-to-undefined-property                               
  :48    Cannot cast mixed to float.                                                                                         
         🪪  cast.double                                                                                                     
  :51    Access to an undefined property App\Models\DispensingRecordItem::$latest_dispensed_at.                              
         🪪  property.notFound                                                                                               
         💡  Learn more: https://phpstan.org/blog/solving-phpstan-access-to-undefined-property                               
  :52    Cannot cast mixed to string.                                                                                        
         🪪  cast.string                                                                                                     

  Line   app\Support\PrescriptionQueueQuery.php                                                         

  :18    Method App\Support\PrescriptionQueueQuery::paginate() return type with generic interface       
         Illuminate\Contracts\Pagination\LengthAwarePaginator does not specify its types: TKey, TValue  
         🪪  missingType.generics                                                                       



  Line   app\Support\ValidatesAppointmentScheduling.php                                                    

  :202   Expression on left side of ?? is not nullable.                                                    
         🪪  nullCoalesce.expr                                                                             
  :202   Using nullsafe method call on non-nullable type App\Enums\ScheduleExceptionType. Use -> instead.  
         🪪  nullsafe.neverNull                                                                            



  Line   app\Support\VisitOrderOptions.php                                                                                   

  :46    Parameter #3 $billableIds of method App\Support\VisitOrderOptions::activeInsurancePriceMap() expects array<int, st  
         ring>, array<mixed> given.                                                                                          
         🪪  argument.type                                                                                                   
  :47    Parameter #3 $billableIds of method App\Support\VisitOrderOptions::activeInsurancePriceMap() expects array<int, st  
         ring>, array<mixed> given.                                                                                          
         🪪  argument.type                                                                                                   
  :48    Parameter #3 $billableIds of method App\Support\VisitOrderOptions::activeInsurancePriceMap() expects array<int, st  
         ring>, array<mixed> given.                                                                                          
         🪪  argument.type                                                                                                   
  :132   Strict comparison using === between string and null will always evaluate to false.                                  
         🪪  identical.alwaysFalse                                                                                           
         💡  Because the type is coming from a PHPDoc, you can turn off this check by setting treatPhpDocTypesAsCertain: fal  
         se in your phpstan.neon.                                                                                            



  Line   app\Support\VisitWorkflowGuard.php                                                                                  

  :50    Instanceof between App\Enums\BillingStatus and App\Enums\BillingStatus will always evaluate to true.                
         🪪  instanceof.alwaysTrue                                                                                           
         💡  Because the type is coming from a PHPDoc, you can turn off this check by setting treatPhpDocTypesAsCertain: fal  
         se in your phpstan.neon.                                                                                            



  Line   app\Traits\BelongsToBranch.php (in context of class App\Models\DispensingRecord)  

  :27    Cannot access property $branch_id on mixed.                                       
         🪪  property.nonObject                                                            
  :31    Cannot access property $branch_id on mixed.                                       
         🪪  property.nonObject                                                            



  Line   app\Traits\BelongsToBranch.php (in context of class App\Models\GoodsReceipt)  

  :27    Cannot access property $branch_id on mixed.                                   
         🪪  property.nonObject                                                        
  :31    Cannot access property $branch_id on mixed.                                   
         🪪  property.nonObject                                                        



  Line   app\Traits\BelongsToBranch.php (in context of class App\Models\InventoryBatch)  

  :27    Cannot access property $branch_id on mixed.                                     
         🪪  property.nonObject                                                          
  :31    Cannot access property $branch_id on mixed.                                     
         🪪  property.nonObject                                                          



  Line   app\Traits\BelongsToBranch.php (in context of class App\Models\InventoryLocation)  

  :27    Cannot access property $branch_id on mixed.                                        
         🪪  property.nonObject                                                             
  :31    Cannot access property $branch_id on mixed.                                        
         🪪  property.nonObject                                                             


-
  Line   app\Traits\BelongsToBranch.php (in context of class App\Models\InventoryLocationItem)  
-
  :27    Cannot access property $branch_id on mixed.                                            
         🪪  property.nonObject                                                                 
  :31    Cannot access property $branch_id on mixed.                                            
         🪪  property.nonObject                                                                 
-


  Line   app\Traits\BelongsToBranch.php (in context of class App\Models\InventoryRequisition)  

  :27    Cannot access property $branch_id on mixed.                                           
         🪪  property.nonObject                                                                
  :31    Cannot access property $branch_id on mixed.                                           
         🪪  property.nonObject                                                                


-
  Line   app\Traits\BelongsToBranch.php (in context of class App\Models\PharmacyPosCart)  
-
  :27    Cannot access property $branch_id on mixed.                                      
         🪪  property.nonObject                                                           
  :31    Cannot access property $branch_id on mixed.                                      
         🪪  property.nonObject                                                           
-

-
  Line   app\Traits\BelongsToBranch.php (in context of class App\Models\PharmacyPosSale)  
-
  :27    Cannot access property $branch_id on mixed.                                      
         🪪  property.nonObject                                                           
  :31    Cannot access property $branch_id on mixed.                                      
         🪪  property.nonObject                                                           
-

 
  Line   app\Traits\BelongsToBranch.php (in context of class App\Models\PurchaseOrder)  
 
  :27    Cannot access property $branch_id on mixed.                                    
         🪪  property.nonObject                                                         
  :31    Cannot access property $branch_id on mixed.                                    
         🪪  property.nonObject                                                         
 


  Line   app\Traits\BelongsToBranch.php (in context of class App\Models\Reconciliation)  

  :27    Cannot access property $branch_id on mixed.                                     
         🪪  property.nonObject                                                          
  :31    Cannot access property $branch_id on mixed.                                     
         🪪  property.nonObject                                                          


 
  Line   app\Traits\BelongsToBranch.php (in context of class App\Models\StockMovement)  
 
  :27    Cannot access property $branch_id on mixed.                                    
         🪪  property.nonObject                                                         
  :31    Cannot access property $branch_id on mixed.                                    
         🪪  property.nonObject                                                         
 

- 
  Line   app\Traits\BelongsToTenant.php (in context of class App\Models\Allergen)  
- 
  :27    Cannot access property $tenant_id on App\Models\User|null.                
         🪪  property.nonObject                                                    
  :27    Cannot access property $tenant_id on mixed.                               
         🪪  property.nonObject                                                    
  :28    Cannot access property $tenant_id on App\Models\User|null.                
         🪪  property.nonObject                                                    
  :28    Cannot access property $tenant_id on mixed.                               
         🪪  property.nonObject                                                    
- 

- 
  Line   app\Traits\BelongsToTenant.php (in context of class App\Models\Appointment)  
- 
  :27    Cannot access property $tenant_id on App\Models\User|null.                   
         🪪  property.nonObject                                                       
  :27    Cannot access property $tenant_id on mixed.                                  
         🪪  property.nonObject                                                       
  :28    Cannot access property $tenant_id on App\Models\User|null.                   
         🪪  property.nonObject                                                       
  :28    Cannot access property $tenant_id on mixed.                                  
         🪪  property.nonObject                                                       
- 

--
  Line   app\Traits\BelongsToTenant.php (in context of class App\Models\AppointmentCategory)  
--
  :27    Cannot access property $tenant_id on App\Models\User|null.                           
         🪪  property.nonObject                                                               
  :27    Cannot access property $tenant_id on mixed.                                          
         🪪  property.nonObject                                                               
  :28    Cannot access property $tenant_id on App\Models\User|null.                           
         🪪  property.nonObject                                                               
  :28    Cannot access property $tenant_id on mixed.                                          
         🪪  property.nonObject                                                               
--

-
  Line   app\Traits\BelongsToTenant.php (in context of class App\Models\AppointmentMode)  
-
  :27    Cannot access property $tenant_id on App\Models\User|null.                       
         🪪  property.nonObject                                                           
  :27    Cannot access property $tenant_id on mixed.                                      
         🪪  property.nonObject                                                           
  :28    Cannot access property $tenant_id on App\Models\User|null.                       
         🪪  property.nonObject                                                           
  :28    Cannot access property $tenant_id on mixed.                                      
         🪪  property.nonObject                                                           
-

-- 
  Line   app\Traits\BelongsToTenant.php (in context of class App\Models\Clinic)  
-- 
  :27    Cannot access property $tenant_id on App\Models\User|null.              
         🪪  property.nonObject                                                  
  :27    Cannot access property $tenant_id on mixed.                             
         🪪  property.nonObject                                                  
  :28    Cannot access property $tenant_id on App\Models\User|null.              
         🪪  property.nonObject                                                  
  :28    Cannot access property $tenant_id on mixed.                             
         🪪  property.nonObject                                                  
-- 


  Line   app\Traits\BelongsToTenant.php (in context of class App\Models\Consultation)  

  :27    Cannot access property $tenant_id on App\Models\User|null.                    
         🪪  property.nonObject                                                        
  :27    Cannot access property $tenant_id on mixed.                                   
         🪪  property.nonObject                                                        
  :28    Cannot access property $tenant_id on App\Models\User|null.                    
         🪪  property.nonObject                                                        
  :28    Cannot access property $tenant_id on mixed.                                   
         🪪  property.nonObject                                                        


 
  Line   app\Traits\BelongsToTenant.php (in context of class App\Models\Department)  
 
  :27    Cannot access property $tenant_id on App\Models\User|null.                  
         🪪  property.nonObject                                                      
  :27    Cannot access property $tenant_id on mixed.                                 
         🪪  property.nonObject                                                      
  :28    Cannot access property $tenant_id on App\Models\User|null.                  
         🪪  property.nonObject                                                      
  :28    Cannot access property $tenant_id on mixed.                                 
         🪪  property.nonObject                                                      
 


  Line   app\Traits\BelongsToTenant.php (in context of class App\Models\DispensingRecord)  

  :27    Cannot access property $tenant_id on App\Models\User|null.                        
         🪪  property.nonObject                                                            
  :27    Cannot access property $tenant_id on mixed.                                       
         🪪  property.nonObject                                                            
  :28    Cannot access property $tenant_id on App\Models\User|null.                        
         🪪  property.nonObject                                                            
  :28    Cannot access property $tenant_id on mixed.                                       
         🪪  property.nonObject                                                            



  Line   app\Traits\BelongsToTenant.php (in context of class App\Models\DoctorSchedule)  

  :27    Cannot access property $tenant_id on App\Models\User|null.                      
         🪪  property.nonObject                                                          
  :27    Cannot access property $tenant_id on mixed.                                     
         🪪  property.nonObject                                                          
  :28    Cannot access property $tenant_id on App\Models\User|null.                      
         🪪  property.nonObject                                                          
  :28    Cannot access property $tenant_id on mixed.                                     
         🪪  property.nonObject                                                          



  Line   app\Traits\BelongsToTenant.php (in context of class App\Models\DoctorScheduleException)  

  :27    Cannot access property $tenant_id on App\Models\User|null.                               
         🪪  property.nonObject                                                                   
  :27    Cannot access property $tenant_id on mixed.                                              
         🪪  property.nonObject                                                                   
  :28    Cannot access property $tenant_id on App\Models\User|null.                               
         🪪  property.nonObject                                                                   
  :28    Cannot access property $tenant_id on mixed.                                              
         🪪  property.nonObject                                                                   



  Line   app\Traits\BelongsToTenant.php (in context of class App\Models\FacilityBranch)  

  :27    Cannot access property $tenant_id on App\Models\User|null.                      
         🪪  property.nonObject                                                          
  :27    Cannot access property $tenant_id on mixed.                                     
         🪪  property.nonObject                                                          
  :28    Cannot access property $tenant_id on App\Models\User|null.                      
         🪪  property.nonObject                                                          
  :28    Cannot access property $tenant_id on mixed.                                     
         🪪  property.nonObject                                                          


-
  Line   app\Traits\BelongsToTenant.php (in context of class App\Models\FacilityService)  
-
  :27    Cannot access property $tenant_id on App\Models\User|null.                       
         🪪  property.nonObject                                                           
  :27    Cannot access property $tenant_id on mixed.                                      
         🪪  property.nonObject                                                           
  :28    Cannot access property $tenant_id on App\Models\User|null.                       
         🪪  property.nonObject                                                           
  :28    Cannot access property $tenant_id on mixed.                                      
         🪪  property.nonObject                                                           
-


  Line   app\Traits\BelongsToTenant.php (in context of class App\Models\FacilityServiceOrder)  

  :27    Cannot access property $tenant_id on App\Models\User|null.                            
         🪪  property.nonObject                                                                
  :27    Cannot access property $tenant_id on mixed.                                           
         🪪  property.nonObject                                                                
  :28    Cannot access property $tenant_id on App\Models\User|null.                            
         🪪  property.nonObject                                                                
  :28    Cannot access property $tenant_id on mixed.                                           
         🪪  property.nonObject                                                                



  Line   app\Traits\BelongsToTenant.php (in context of class App\Models\GoodsReceipt)  

  :27    Cannot access property $tenant_id on App\Models\User|null.                    
         🪪  property.nonObject                                                        
  :27    Cannot access property $tenant_id on mixed.                                   
         🪪  property.nonObject                                                        
  :28    Cannot access property $tenant_id on App\Models\User|null.                    
         🪪  property.nonObject                                                        
  :28    Cannot access property $tenant_id on mixed.                                   
         🪪  property.nonObject                                                        



  Line   app\Traits\BelongsToTenant.php (in context of class App\Models\InsuranceCompany)  

  :27    Cannot access property $tenant_id on App\Models\User|null.                        
         🪪  property.nonObject                                                            
  :27    Cannot access property $tenant_id on mixed.                                       
         🪪  property.nonObject                                                            
  :28    Cannot access property $tenant_id on App\Models\User|null.                        
         🪪  property.nonObject                                                            
  :28    Cannot access property $tenant_id on mixed.                                       
         🪪  property.nonObject                                                            



  Line   app\Traits\BelongsToTenant.php (in context of class App\Models\InsuranceCompanyInvoice)  

  :27    Cannot access property $tenant_id on App\Models\User|null.                               
         🪪  property.nonObject                                                                   
  :27    Cannot access property $tenant_id on mixed.                                              
         🪪  property.nonObject                                                                   
  :28    Cannot access property $tenant_id on App\Models\User|null.                               
         🪪  property.nonObject                                                                   
  :28    Cannot access property $tenant_id on mixed.                                              
         🪪  property.nonObject                                                                   


-
  Line   app\Traits\BelongsToTenant.php (in context of class App\Models\InsuranceCompanyInvoicePayment)  
-
  :27    Cannot access property $tenant_id on App\Models\User|null.                                      
         🪪  property.nonObject                                                                          
  :27    Cannot access property $tenant_id on mixed.                                                     
         🪪  property.nonObject                                                                          
  :28    Cannot access property $tenant_id on App\Models\User|null.                                      
         🪪  property.nonObject                                                                          
  :28    Cannot access property $tenant_id on mixed.                                                     
         🪪  property.nonObject                                                                          
-


  Line   app\Traits\BelongsToTenant.php (in context of class App\Models\InsurancePackage)  

  :27    Cannot access property $tenant_id on App\Models\User|null.                        
         🪪  property.nonObject                                                            
  :27    Cannot access property $tenant_id on mixed.                                       
         🪪  property.nonObject                                                            
  :28    Cannot access property $tenant_id on App\Models\User|null.                        
         🪪  property.nonObject                                                            
  :28    Cannot access property $tenant_id on mixed.                                       
         🪪  property.nonObject                                                            


-
  Line   app\Traits\BelongsToTenant.php (in context of class App\Models\InsurancePackagePrice)  
-
  :27    Cannot access property $tenant_id on App\Models\User|null.                             
         🪪  property.nonObject                                                                 
  :27    Cannot access property $tenant_id on mixed.                                            
         🪪  property.nonObject                                                                 
  :28    Cannot access property $tenant_id on App\Models\User|null.                             
         🪪  property.nonObject                                                                 
  :28    Cannot access property $tenant_id on mixed.                                            
         🪪  property.nonObject                                                                 
-


  Line   app\Traits\BelongsToTenant.php (in context of class App\Models\InventoryBatch)  

  :27    Cannot access property $tenant_id on App\Models\User|null.                      
         🪪  property.nonObject                                                          
  :27    Cannot access property $tenant_id on mixed.                                     
         🪪  property.nonObject                                                          
  :28    Cannot access property $tenant_id on App\Models\User|null.                      
         🪪  property.nonObject                                                          
  :28    Cannot access property $tenant_id on mixed.                                     
         🪪  property.nonObject                                                          


 
  Line   app\Traits\BelongsToTenant.php (in context of class App\Models\InventoryItem)  
 
  :27    Cannot access property $tenant_id on App\Models\User|null.                     
         🪪  property.nonObject                                                         
  :27    Cannot access property $tenant_id on mixed.                                    
         🪪  property.nonObject                                                         
  :28    Cannot access property $tenant_id on App\Models\User|null.                     
         🪪  property.nonObject                                                         
  :28    Cannot access property $tenant_id on mixed.                                    
         🪪  property.nonObject                                                         
 


  Line   app\Traits\BelongsToTenant.php (in context of class App\Models\InventoryLocation)  

  :27    Cannot access property $tenant_id on App\Models\User|null.                         
         🪪  property.nonObject                                                             
  :27    Cannot access property $tenant_id on mixed.                                        
         🪪  property.nonObject                                                             
  :28    Cannot access property $tenant_id on App\Models\User|null.                         
         🪪  property.nonObject                                                             
  :28    Cannot access property $tenant_id on mixed.                                        
         🪪  property.nonObject                                                             


-
  Line   app\Traits\BelongsToTenant.php (in context of class App\Models\InventoryLocationItem)  
-
  :27    Cannot access property $tenant_id on App\Models\User|null.                             
         🪪  property.nonObject                                                                 
  :27    Cannot access property $tenant_id on mixed.                                            
         🪪  property.nonObject                                                                 
  :28    Cannot access property $tenant_id on App\Models\User|null.                             
         🪪  property.nonObject                                                                 
  :28    Cannot access property $tenant_id on mixed.                                            
         🪪  property.nonObject                                                                 
-


  Line   app\Traits\BelongsToTenant.php (in context of class App\Models\InventoryRequisition)  

  :27    Cannot access property $tenant_id on App\Models\User|null.                            
         🪪  property.nonObject                                                                
  :27    Cannot access property $tenant_id on mixed.                                           
         🪪  property.nonObject                                                                
  :28    Cannot access property $tenant_id on App\Models\User|null.                            
         🪪  property.nonObject                                                                
  :28    Cannot access property $tenant_id on mixed.                                           
         🪪  property.nonObject                                                                


 
  Line   app\Traits\BelongsToTenant.php (in context of class App\Models\LabRequest)  
 
  :27    Cannot access property $tenant_id on App\Models\User|null.                  
         🪪  property.nonObject                                                      
  :27    Cannot access property $tenant_id on mixed.                                 
         🪪  property.nonObject                                                      
  :28    Cannot access property $tenant_id on App\Models\User|null.                  
         🪪  property.nonObject                                                      
  :28    Cannot access property $tenant_id on mixed.                                 
         🪪  property.nonObject                                                      
 

-
  Line   app\Traits\BelongsToTenant.php (in context of class App\Models\LabRequestItemConsumable)  
-
  :27    Cannot access property $tenant_id on App\Models\User|null.                                
         🪪  property.nonObject                                                                    
  :27    Cannot access property $tenant_id on mixed.                                               
         🪪  property.nonObject                                                                    
  :28    Cannot access property $tenant_id on App\Models\User|null.                                
         🪪  property.nonObject                                                                    
  :28    Cannot access property $tenant_id on mixed.                                               
         🪪  property.nonObject                                                                    
-

 
  Line   app\Traits\BelongsToTenant.php (in context of class App\Models\LabResultType)  
 
  :27    Cannot access property $tenant_id on App\Models\User|null.                     
         🪪  property.nonObject                                                         
  :27    Cannot access property $tenant_id on mixed.                                    
         🪪  property.nonObject                                                         
  :28    Cannot access property $tenant_id on App\Models\User|null.                     
         🪪  property.nonObject                                                         
  :28    Cannot access property $tenant_id on mixed.                                    
         🪪  property.nonObject                                                         
 


  Line   app\Traits\BelongsToTenant.php (in context of class App\Models\LabTestCatalog)  

  :27    Cannot access property $tenant_id on App\Models\User|null.                      
         🪪  property.nonObject                                                          
  :27    Cannot access property $tenant_id on mixed.                                     
         🪪  property.nonObject                                                          
  :28    Cannot access property $tenant_id on App\Models\User|null.                      
         🪪  property.nonObject                                                          
  :28    Cannot access property $tenant_id on mixed.                                     
         🪪  property.nonObject                                                          


-
  Line   app\Traits\BelongsToTenant.php (in context of class App\Models\LabTestCategory)  
-
  :27    Cannot access property $tenant_id on App\Models\User|null.                       
         🪪  property.nonObject                                                           
  :27    Cannot access property $tenant_id on mixed.                                      
         🪪  property.nonObject                                                           
  :28    Cannot access property $tenant_id on App\Models\User|null.                       
         🪪  property.nonObject                                                           
  :28    Cannot access property $tenant_id on mixed.                                      
         🪪  property.nonObject                                                           
-

 
  Line   app\Traits\BelongsToTenant.php (in context of class App\Models\Patient)  
 
  :27    Cannot access property $tenant_id on App\Models\User|null.               
         🪪  property.nonObject                                                   
  :27    Cannot access property $tenant_id on mixed.                              
         🪪  property.nonObject                                                   
  :28    Cannot access property $tenant_id on App\Models\User|null.               
         🪪  property.nonObject                                                   
  :28    Cannot access property $tenant_id on mixed.                              
         🪪  property.nonObject                                                   
 


  Line   app\Traits\BelongsToTenant.php (in context of class App\Models\PatientAllergy)  

  :27    Cannot access property $tenant_id on App\Models\User|null.                      
         🪪  property.nonObject                                                          
  :27    Cannot access property $tenant_id on mixed.                                     
         🪪  property.nonObject                                                          
  :28    Cannot access property $tenant_id on App\Models\User|null.                      
         🪪  property.nonObject                                                          
  :28    Cannot access property $tenant_id on mixed.                                     
         🪪  property.nonObject                                                          



  Line   app\Traits\BelongsToTenant.php (in context of class App\Models\PatientVisit)  

  :27    Cannot access property $tenant_id on App\Models\User|null.                    
         🪪  property.nonObject                                                        
  :27    Cannot access property $tenant_id on mixed.                                   
         🪪  property.nonObject                                                        
  :28    Cannot access property $tenant_id on App\Models\User|null.                    
         🪪  property.nonObject                                                        
  :28    Cannot access property $tenant_id on mixed.                                   
         🪪  property.nonObject                                                        


 
  Line   app\Traits\BelongsToTenant.php (in context of class App\Models\Payment)  
 
  :27    Cannot access property $tenant_id on App\Models\User|null.               
         🪪  property.nonObject                                                   
  :27    Cannot access property $tenant_id on mixed.                              
         🪪  property.nonObject                                                   
  :28    Cannot access property $tenant_id on App\Models\User|null.               
         🪪  property.nonObject                                                   
  :28    Cannot access property $tenant_id on mixed.                              
         🪪  property.nonObject                                                   
 

-
  Line   app\Traits\BelongsToTenant.php (in context of class App\Models\PharmacyPosCart)  
-
  :27    Cannot access property $tenant_id on App\Models\User|null.                       
         🪪  property.nonObject                                                           
  :27    Cannot access property $tenant_id on mixed.                                      
         🪪  property.nonObject                                                           
  :28    Cannot access property $tenant_id on App\Models\User|null.                       
         🪪  property.nonObject                                                           
  :28    Cannot access property $tenant_id on mixed.                                      
         🪪  property.nonObject                                                           
-

-
  Line   app\Traits\BelongsToTenant.php (in context of class App\Models\PharmacyPosSale)  
-
  :27    Cannot access property $tenant_id on App\Models\User|null.                       
         🪪  property.nonObject                                                           
  :27    Cannot access property $tenant_id on mixed.                                      
         🪪  property.nonObject                                                           
  :28    Cannot access property $tenant_id on App\Models\User|null.                       
         🪪  property.nonObject                                                           
  :28    Cannot access property $tenant_id on mixed.                                      
         🪪  property.nonObject                                                           
-

 
  Line   app\Traits\BelongsToTenant.php (in context of class App\Models\PurchaseOrder)  
 
  :27    Cannot access property $tenant_id on App\Models\User|null.                     
         🪪  property.nonObject                                                         
  :27    Cannot access property $tenant_id on mixed.                                    
         🪪  property.nonObject                                                         
  :28    Cannot access property $tenant_id on App\Models\User|null.                     
         🪪  property.nonObject                                                         
  :28    Cannot access property $tenant_id on mixed.                                    
         🪪  property.nonObject                                                         
 


  Line   app\Traits\BelongsToTenant.php (in context of class App\Models\Reconciliation)  



                                                                                                                        
 [ERROR] Found 1000+ errors                                                                                             
                                                                                                                        

 ! [NOTE] Result is limited to the first 1000 errors
- Consider lowering the PHPStan level
- Pass                         
 !        PHPSTAN_TABLE_ERROR_FORMATTER_FORCE_SHOW_ALL_ERRORS=1 environment variable to show all errors
- Consider using 
 !        PHPStan Pro for more comfortable error browsing
  Learn more: https://phpstan.com                              

PS C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2> 

