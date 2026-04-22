🪪  class.notFound                                                                                                  
  :18    Type Database\Factories\LabRequestItemConsumableFactory in generic type                                             
         Illuminate\Database\Eloquent\Factories\HasFactory<Database\Factories\LabRequestItemConsumableFactory> in PHPDoc ta  
         g @use is not subtype of template type TFactory of Illuminate\Database\Eloquent\Factories\Factory of trait          
         Illuminate\Database\Eloquent\Factories\HasFactory.                                                                  
         🪪  generics.notSubtype                                                                                             
  :33    Method App\Models\LabRequestItemConsumable::requestItem() return type with generic class                            
         Illuminate\Database\Eloquent\Relations\BelongsTo does not specify its types: TRelatedModel, TDeclaringModel         
         🪪  missingType.generics                                                                                            
  :38    Method App\Models\LabRequestItemConsumable::recordedBy() return type with generic class                             
         Illuminate\Database\Eloquent\Relations\BelongsTo does not specify its types: TRelatedModel, TDeclaringModel         
         🪪  missingType.generics                                                                                            



  Line   app\Models\LabResultEntry.php                                                                                       

  :16    PHPDoc tag @use has invalid type Database\Factories\LabResultEntryFactory.                                          
         🪪  class.notFound                                                                                                  
  :16    Type Database\Factories\LabResultEntryFactory in generic type Illuminate\Database\Eloquent\Factories\HasFactory<Da  
         tabase\Factories\LabResultEntryFactory> in PHPDoc tag @use is not subtype of template type TFactory of Illuminate\  
         Database\Eloquent\Factories\Factory of trait Illuminate\Database\Eloquent\Factories\HasFactory.                     
         🪪  generics.notSubtype                                                                                             



  Line   app\Models\LabResultType.php                                                                                        

  :18    PHPDoc tag @use has invalid type Database\Factories\LabResultTypeFactory.                                           
         🪪  class.notFound                                                                                                  
  :18    Type Database\Factories\LabResultTypeFactory in generic type Illuminate\Database\Eloquent\Factories\HasFactory<Dat  
         abase\Factories\LabResultTypeFactory> in PHPDoc tag @use is not subtype of template type TFactory of Illuminate\Da  
         tabase\Eloquent\Factories\Factory of trait Illuminate\Database\Eloquent\Factories\HasFactory.                       
         🪪  generics.notSubtype                                                                                             
  :29    Method App\Models\LabResultType::labTests() return type with generic class                                          
         Illuminate\Database\Eloquent\Relations\HasMany does not specify its types: TRelatedModel, TDeclaringModel           
         🪪  missingType.generics                                                                                            



  Line   app\Models\LabResultValue.php                                                                                       

  :16    PHPDoc tag @use has invalid type Database\Factories\LabResultValueFactory.                                          
         🪪  class.notFound                                                                                                  
  :16    Type Database\Factories\LabResultValueFactory in generic type Illuminate\Database\Eloquent\Factories\HasFactory<Da  
         tabase\Factories\LabResultValueFactory> in PHPDoc tag @use is not subtype of template type TFactory of Illuminate\  
         Database\Eloquent\Factories\Factory of trait Illuminate\Database\Eloquent\Factories\HasFactory.                     
         🪪  generics.notSubtype                                                                                             
  :33    Method App\Models\LabResultValue::entry() return type with generic class                                            
         Illuminate\Database\Eloquent\Relations\BelongsTo does not specify its types: TRelatedModel, TDeclaringModel         
         🪪  missingType.generics                                                                                            
  :38    Method App\Models\LabResultValue::parameter() return type with generic class                                        
         Illuminate\Database\Eloquent\Relations\BelongsTo does not specify its types: TRelatedModel, TDeclaringModel         
         🪪  missingType.generics                                                                                            
  :43    Method App\Models\LabResultValue::displayValue() return type with generic class                                     
         Illuminate\Database\Eloquent\Casts\Attribute does not specify its types: TGet, TSet                                 
         🪪  missingType.generics                                                                                            



  Line   app\Models\LabSpecimen.php                                                                                          

  :16    PHPDoc tag @use has invalid type Database\Factories\LabSpecimenFactory.                                             
         🪪  class.notFound                                                                                                  
  :16    Type Database\Factories\LabSpecimenFactory in generic type Illuminate\Database\Eloquent\Factories\HasFactory<Datab  
         ase\Factories\LabSpecimenFactory> in PHPDoc tag @use is not subtype of template type TFactory of Illuminate\Databa  
         se\Eloquent\Factories\Factory of trait Illuminate\Database\Eloquent\Factories\HasFactory.                           
         🪪  generics.notSubtype                                                                                             
  :31    Method App\Models\LabSpecimen::requestItem() return type with generic class                                         
         Illuminate\Database\Eloquent\Relations\BelongsTo does not specify its types: TRelatedModel, TDeclaringModel         
         🪪  missingType.generics                                                                                            
  :36    Method App\Models\LabSpecimen::specimenType() return type with generic class                                        
         Illuminate\Database\Eloquent\Relations\BelongsTo does not specify its types: TRelatedModel, TDeclaringModel         
         🪪  missingType.generics                                                                                            
  :41    Method App\Models\LabSpecimen::collectedBy() return type with generic class                                         
         Illuminate\Database\Eloquent\Relations\BelongsTo does not specify its types: TRelatedModel, TDeclaringModel         
         🪪  missingType.generics                                                                                            
  :46    Method App\Models\LabSpecimen::rejectedBy() return type with generic class                                          
         Illuminate\Database\Eloquent\Relations\BelongsTo does not specify its types: TRelatedModel, TDeclaringModel         
         🪪  missingType.generics                                                                                            



  Line   app\Models\LabTestCatalog.php                                                                                       

  :21    PHPDoc tag @use has invalid type Database\Factories\LabTestCatalogFactory.                                          
         🪪  class.notFound                                                                                                  
  :21    Type Database\Factories\LabTestCatalogFactory in generic type Illuminate\Database\Eloquent\Factories\HasFactory<Da  
         tabase\Factories\LabTestCatalogFactory> in PHPDoc tag @use is not subtype of template type TFactory of Illuminate\  
         Database\Eloquent\Factories\Factory of trait Illuminate\Database\Eloquent\Factories\HasFactory.                     
         🪪  generics.notSubtype                                                                                             



  Line   app\Models\LabTestCategory.php                                                                                      

  :18    PHPDoc tag @use has invalid type Database\Factories\LabTestCategoryFactory.                                         
         🪪  class.notFound                                                                                                  
  :18    Type Database\Factories\LabTestCategoryFactory in generic type Illuminate\Database\Eloquent\Factories\HasFactory<D  
         atabase\Factories\LabTestCategoryFactory> in PHPDoc tag @use is not subtype of template type TFactory of Illuminat  
         e\Database\Eloquent\Factories\Factory of trait Illuminate\Database\Eloquent\Factories\HasFactory.                   
         🪪  generics.notSubtype                                                                                             
  :27    Method App\Models\LabTestCategory::labTests() return type with generic class                                        
         Illuminate\Database\Eloquent\Relations\HasMany does not specify its types: TRelatedModel, TDeclaringModel           
         🪪  missingType.generics                                                                                            


  Line   app\Models\LabTestResultOption.php                                                                                  

  :15    PHPDoc tag @use has invalid type Database\Factories\LabTestResultOptionFactory.                                     
         🪪  class.notFound                                                                                                  
  :15    Type Database\Factories\LabTestResultOptionFactory in generic type                                                  
         Illuminate\Database\Eloquent\Factories\HasFactory<Database\Factories\LabTestResultOptionFactory> in PHPDoc tag @us  
         e is not subtype of template type TFactory of Illuminate\Database\Eloquent\Factories\Factory of trait               
         Illuminate\Database\Eloquent\Factories\HasFactory.                                                                  
         🪪  generics.notSubtype                                                                                             
  :25    Method App\Models\LabTestResultOption::labTest() return type with generic class                                     
         Illuminate\Database\Eloquent\Relations\BelongsTo does not specify its types: TRelatedModel, TDeclaringModel         
         🪪  missingType.generics                                                                                            



  Line   app\Models\LabTestResultParameter.php                                                                               

  :15    PHPDoc tag @use has invalid type Database\Factories\LabTestResultParameterFactory.                                  
         🪪  class.notFound                                                                                                  
  :15    Type Database\Factories\LabTestResultParameterFactory in generic type                                               
         Illuminate\Database\Eloquent\Factories\HasFactory<Database\Factories\LabTestResultParameterFactory> in PHPDoc tag   
         @use is not subtype of template type TFactory of Illuminate\Database\Eloquent\Factories\Factory of trait            
         Illuminate\Database\Eloquent\Factories\HasFactory.                                                                  
         🪪  generics.notSubtype                                                                                             
  :27    Method App\Models\LabTestResultParameter::labTest() return type with generic class                                  
         Illuminate\Database\Eloquent\Relations\BelongsTo does not specify its types: TRelatedModel, TDeclaringModel         
         🪪  missingType.generics                                                                                            



  Line   app\Models\Patient.php                                                                                              

  :21    PHPDoc tag @use has invalid type Database\Factories\PatientFactory.                                                 
         🪪  class.notFound                                                                                                  
  :21    Type Database\Factories\PatientFactory in generic type Illuminate\Database\Eloquent\Factories\HasFactory<Database\  
         Factories\PatientFactory> in PHPDoc tag @use is not subtype of template type TFactory of Illuminate\Database\Eloqu  
         ent\Factories\Factory of trait Illuminate\Database\Eloquent\Factories\HasFactory.                                   
         🪪  generics.notSubtype                                                                                             
  :38    Method App\Models\Patient::tenant() return type with generic class                                                  
         Illuminate\Database\Eloquent\Relations\BelongsTo does not specify its types: TRelatedModel, TDeclaringModel         
         🪪  missingType.generics                                                                                            
  :43    Method App\Models\Patient::country() return type with generic class                                                 
         Illuminate\Database\Eloquent\Relations\BelongsTo does not specify its types: TRelatedModel, TDeclaringModel         
         🪪  missingType.generics                                                                                            
  :48    Method App\Models\Patient::address() return type with generic class                                                 
         Illuminate\Database\Eloquent\Relations\BelongsTo does not specify its types: TRelatedModel, TDeclaringModel         
         🪪  missingType.generics                                                                                            
  :53    Method App\Models\Patient::allergies() return type with generic class                                               
         Illuminate\Database\Eloquent\Relations\HasMany does not specify its types: TRelatedModel, TDeclaringModel           
         🪪  missingType.generics                                                                                            
  :58    Method App\Models\Patient::activeAllergies() return type with generic class                                         
         Illuminate\Database\Eloquent\Relations\HasMany does not specify its types: TRelatedModel, TDeclaringModel           
         🪪  missingType.generics                                                                                            
  :63    Method App\Models\Patient::visits() return type with generic class Illuminate\Database\Eloquent\Relations\HasMany   
         does not specify its types: TRelatedModel, TDeclaringModel                                                          
         🪪  missingType.generics                                                                                            
  :83    Match arm comparison between 'day' and 'day' is always true.                                                        
         🪪  match.alwaysTrue                                                                                                
         💡  Remove remaining cases below this one and this error will disappear too.                                        
  :105   Cannot cast mixed to int.                                                                                           
         🪪  cast.int                                                                                                        
  :144   Cannot cast mixed to string.                                                                                        
         🪪  cast.string                                                                                                     



  Line   app\Models\PatientAllergy.php                                                                                       

  :23    PHPDoc tag @use has invalid type Database\Factories\PatientAllergyFactory.                                          
         🪪  class.notFound                                                                                                  
  :23    Type Database\Factories\PatientAllergyFactory in generic type Illuminate\Database\Eloquent\Factories\HasFactory<Da  
         tabase\Factories\PatientAllergyFactory> in PHPDoc tag @use is not subtype of template type TFactory of Illuminate\  
         Database\Eloquent\Factories\Factory of trait Illuminate\Database\Eloquent\Factories\HasFactory.                     
         🪪  generics.notSubtype                                                                                             
  :82    Method App\Models\PatientAllergy::active() has parameter $query with generic class                                  
         Illuminate\Database\Eloquent\Builder but does not specify its types: TModel                                         
         🪪  missingType.generics                                                                                            
  :82    Method App\Models\PatientAllergy::active() return type with generic class Illuminate\Database\Eloquent\Builder      
         does not specify its types: TModel                                                                                  
         🪪  missingType.generics                                                                                            
  :91    Method App\Models\PatientAllergy::bySeverity() has parameter $query with generic class                              
         Illuminate\Database\Eloquent\Builder but does not specify its types: TModel                                         
         🪪  missingType.generics                                                                                            
  :91    Method App\Models\PatientAllergy::bySeverity() return type with generic class Illuminate\Database\Eloquent\Builder  
         does not specify its types: TModel                                                                                  
         🪪  missingType.generics                                                                                            



  Line   app\Models\Permission.php                                                                                      

  :13    Class App\Models\Permission uses generic trait Illuminate\Database\Eloquent\Factories\HasFactory but does not  
         specify its types: TFactory                                                                                    
         🪪  missingType.generics                                                                                       



  Line   app\Models\PharmacyPosCartItemAllocation.php                                                                 

  :20    Method App\Models\PharmacyPosCartItemAllocation::cartItem() return type with generic class                   
         Illuminate\Database\Eloquent\Relations\BelongsTo does not specify its types: TRelatedModel, TDeclaringModel  
         🪪  missingType.generics                                                                                     
  :25    Method App\Models\PharmacyPosCartItemAllocation::inventoryBatch() return type with generic class             
         Illuminate\Database\Eloquent\Relations\BelongsTo does not specify its types: TRelatedModel, TDeclaringModel  
         🪪  missingType.generics                                                                                     



  Line   app\Models\PharmacyPosSaleItemAllocation.php                                                                 

  :20    Method App\Models\PharmacyPosSaleItemAllocation::saleItem() return type with generic class                   
         Illuminate\Database\Eloquent\Relations\BelongsTo does not specify its types: TRelatedModel, TDeclaringModel  
         🪪  missingType.generics                                                                                     
  :25    Method App\Models\PharmacyPosSaleItemAllocation::inventoryBatch() return type with generic class             
         Illuminate\Database\Eloquent\Relations\BelongsTo does not specify its types: TRelatedModel, TDeclaringModel  
         🪪  missingType.generics                                                                                     



  Line   app\Models\PurchaseOrder.php                                                                                 

  :43    Method App\Models\PurchaseOrder::supplier() return type with generic class                                   
         Illuminate\Database\Eloquent\Relations\BelongsTo does not specify its types: TRelatedModel, TDeclaringModel  
         🪪  missingType.generics                                                                                     
  :48    Method App\Models\PurchaseOrder::items() return type with generic class                                      
         Illuminate\Database\Eloquent\Relations\HasMany does not specify its types: TRelatedModel, TDeclaringModel    
         🪪  missingType.generics                                                                                     
  :53    Method App\Models\PurchaseOrder::goodsReceipts() return type with generic class                              
         Illuminate\Database\Eloquent\Relations\HasMany does not specify its types: TRelatedModel, TDeclaringModel    
         🪪  missingType.generics                                                                                     



  Line   app\Models\PurchaseOrderItem.php                                                                             

  :29    Method App\Models\PurchaseOrderItem::purchaseOrder() return type with generic class                          
         Illuminate\Database\Eloquent\Relations\BelongsTo does not specify its types: TRelatedModel, TDeclaringModel  
         🪪  missingType.generics                                                                                     
  :34    Method App\Models\PurchaseOrderItem::inventoryItem() return type with generic class                          
         Illuminate\Database\Eloquent\Relations\BelongsTo does not specify its types: TRelatedModel, TDeclaringModel  
         🪪  missingType.generics                                                                                     



  Line   app\Models\Reconciliation.php                                                                                       

  :23    PHPDoc tag @use has invalid type Database\Factories\ReconciliationFactory.                                          
         🪪  class.notFound                                                                                                  
  :23    Type Database\Factories\ReconciliationFactory in generic type Illuminate\Database\Eloquent\Factories\HasFactory<Da  
         tabase\Factories\ReconciliationFactory> in PHPDoc tag @use is not subtype of template type TFactory of Illuminate\  
         Database\Eloquent\Factories\Factory of trait Illuminate\Database\Eloquent\Factories\HasFactory.                     
         🪪  generics.notSubtype                                                                                             
  :50    Method App\Models\Reconciliation::inventoryLocation() return type with generic class                                
         Illuminate\Database\Eloquent\Relations\BelongsTo does not specify its types: TRelatedModel, TDeclaringModel         
         🪪  missingType.generics                                                                                            
  :55    Method App\Models\Reconciliation::items() return type with generic class                                            
         Illuminate\Database\Eloquent\Relations\HasMany does not specify its types: TRelatedModel, TDeclaringModel           
         🪪  missingType.generics                                                                                            



  Line   database\factories\AddressFactory.php                                                                               

  :21    PHPDoc type string of property Database\Factories\AddressFactory::$model is not covariant with PHPDoc type          
         class-string<App\Models\Address> of overridden property Illuminate\Database\Eloquent\Factories\Factory<App\Models\  
         Address>::$model.                                                                                                   
         🪪  property.phpDocType                                                                                             
         💡  You can fix 3rd party PHPDoc types with stub files:                                                             
         💡  https://phpstan.org/user-guide/stub-files                                                                       
  :33    Call to an undefined method Faker\Generator::state().                                                               
         🪪  method.notFound                                                                                                 
  :34    Using nullsafe property access "?->id" on left side of ?? is unnecessary. Use -> instead.                           
         🪪  nullsafe.neverNull                                                                                              



  Line   database\factories\CountryFactory.php                                                                               

  :20    PHPDoc type string of property Database\Factories\CountryFactory::$model is not covariant with PHPDoc type          
         class-string<App\Models\Country> of overridden property Illuminate\Database\Eloquent\Factories\Factory<App\Models\  
         Country>::$model.                                                                                                   
         🪪  property.phpDocType                                                                                             
         💡  You can fix 3rd party PHPDoc types with stub files:                                                             
         💡  https://phpstan.org/user-guide/stub-files                                                                       



  Line   database\factories\InventoryLocationFactory.php                              

  :26    Binary operation "." between array|string and ' Store' results in an error.  
         🪪  binaryOp.invalid                                                         



  Line   database\migrations\2026_03_06_091646_create_permission_tables.php                                                  

  :20    Cannot access offset 'role_pivot_key' on mixed.                                                                     
         🪪  offsetAccess.nonOffsetAccessible                                                                                
  :21    Cannot access offset 'permission_pivot_key' on mixed.                                                               
         🪪  offsetAccess.nonOffsetAccessible                                                                                
  :24    Cannot access offset 'team_foreign_key' on mixed.                                                                   
         🪪  offsetAccess.nonOffsetAccessible                                                                                
  :29    Cannot access offset 'permissions' on mixed.                                                                        
         🪪  offsetAccess.nonOffsetAccessible                                                                                
  :29    Parameter #1 $table of static method Illuminate\Support\Facades\Schema::hasTable() expects string, mixed given.     
         🪪  argument.type                                                                                                   
  :30    Cannot access offset 'permissions' on mixed.                                                                        
         🪪  offsetAccess.nonOffsetAccessible                                                                                
  :30    Parameter #1 $table of static method Illuminate\Support\Facades\Schema::create() expects string, mixed given.       
         🪪  argument.type                                                                                                   
  :43    Cannot access offset 'roles' on mixed.                                                                              
         🪪  offsetAccess.nonOffsetAccessible                                                                                
  :43    Parameter #1 $table of static method Illuminate\Support\Facades\Schema::hasTable() expects string, mixed given.     
         🪪  argument.type                                                                                                   
  :44    Cannot access offset 'roles' on mixed.                                                                              
         🪪  offsetAccess.nonOffsetAccessible                                                                                
  :44    Parameter #1 $table of static method Illuminate\Support\Facades\Schema::create() expects string, mixed given.       
         🪪  argument.type                                                                                                   
  :47    Cannot access offset 'team_foreign_key' on mixed.                                                                   
         🪪  offsetAccess.nonOffsetAccessible                                                                                
  :47    Parameter #1 $column of method Illuminate\Database\Schema\Blueprint::uuid() expects string, mixed given.            
         🪪  argument.type                                                                                                   
  :48    Cannot access offset 'team_foreign_key' on mixed.                                                                   
         🪪  offsetAccess.nonOffsetAccessible                                                                                
  :48    Parameter #1 $columns of method Illuminate\Database\Schema\Blueprint::index() expects array|string, mixed given.    
         🪪  argument.type                                                                                                   
  :55    Cannot access offset 'team_foreign_key' on mixed.                                                                   
         🪪  offsetAccess.nonOffsetAccessible                                                                                
  :62    Cannot access offset 'model_has_permissions' on mixed.                                                              
         🪪  offsetAccess.nonOffsetAccessible                                                                                
  :62    Parameter #1 $table of static method Illuminate\Support\Facades\Schema::hasTable() expects string, mixed given.     
         🪪  argument.type                                                                                                   
  :63    Cannot access offset 'model_has_permissions' on mixed.                                                              
         🪪  offsetAccess.nonOffsetAccessible                                                                                
  :63    Parameter #1 $table of static method Illuminate\Support\Facades\Schema::create() expects string, mixed given.       
         🪪  argument.type                                                                                                   
  :64    Parameter #1 $column of method Illuminate\Database\Schema\Blueprint::uuid() expects string, mixed given.            
         🪪  argument.type                                                                                                   
  :67    Cannot access offset 'model_morph_key' on mixed.                                                                    
         🪪  offsetAccess.nonOffsetAccessible                                                                                
  :67    Parameter #1 $column of method Illuminate\Database\Schema\Blueprint::uuid() expects string, mixed given.            
         🪪  argument.type                                                                                                   
  :68    Cannot access offset 'model_morph_key' on mixed.                                                                    
         🪪  offsetAccess.nonOffsetAccessible                                                                                
  :70    Parameter #1 $columns of method Illuminate\Database\Schema\Blueprint::foreign() expects array|string, mixed given.  
         🪪  argument.type                                                                                                   
  :72    Cannot access offset 'permissions' on mixed.                                                                        
         🪪  offsetAccess.nonOffsetAccessible                                                                                
  :72    Parameter #1 $table of method Illuminate\Database\Schema\ForeignKeyDefinition::on() expects string, mixed given.    
         🪪  argument.type                                                                                                   
  :75    Cannot access offset 'team_foreign_key' on mixed.                                                                   
         🪪  offsetAccess.nonOffsetAccessible                                                                                
  :75    Parameter #1 $column of method Illuminate\Database\Schema\Blueprint::unsignedBigInteger() expects string, mixed     
         given.                                                                                                              
         🪪  argument.type                                                                                                   
  :76    Cannot access offset 'team_foreign_key' on mixed.                                                                   
         🪪  offsetAccess.nonOffsetAccessible                                                                                
  :76    Parameter #1 $columns of method Illuminate\Database\Schema\Blueprint::index() expects array|string, mixed given.    
         🪪  argument.type                                                                                                   
  :78    Cannot access offset 'model_morph_key' on mixed.                                                                    
         🪪  offsetAccess.nonOffsetAccessible                                                                                
  :78    Cannot access offset 'team_foreign_key' on mixed.                                                                   
         🪪  offsetAccess.nonOffsetAccessible                                                                                
  :81    Cannot access offset 'model_morph_key' on mixed.                                                                    
         🪪  offsetAccess.nonOffsetAccessible                                                                                
  :87    Cannot access offset 'model_has_roles' on mixed.                                                                    
         🪪  offsetAccess.nonOffsetAccessible                                                                                
  :87    Parameter #1 $table of static method Illuminate\Support\Facades\Schema::hasTable() expects string, mixed given.     
         🪪  argument.type                                                                                                   
  :88    Cannot access offset 'model_has_roles' on mixed.                                                                    
         🪪  offsetAccess.nonOffsetAccessible                                                                                
  :88    Parameter #1 $table of static method Illuminate\Support\Facades\Schema::create() expects string, mixed given.       
         🪪  argument.type                                                                                                   
  :89    Parameter #1 $column of method Illuminate\Database\Schema\Blueprint::uuid() expects string, mixed given.            
         🪪  argument.type                                                                                                   
  :92    Cannot access offset 'model_morph_key' on mixed.                                                                    
         🪪  offsetAccess.nonOffsetAccessible                                                                                
  :92    Parameter #1 $column of method Illuminate\Database\Schema\Blueprint::uuid() expects string, mixed given.            
         🪪  argument.type                                                                                                   
  :93    Cannot access offset 'model_morph_key' on mixed.                                                                    
         🪪  offsetAccess.nonOffsetAccessible                                                                                
  :95    Parameter #1 $columns of method Illuminate\Database\Schema\Blueprint::foreign() expects array|string, mixed given.  
         🪪  argument.type                                                                                                   
  :97    Cannot access offset 'roles' on mixed.                                                                              
         🪪  offsetAccess.nonOffsetAccessible                                                                                
  :97    Parameter #1 $table of method Illuminate\Database\Schema\ForeignKeyDefinition::on() expects string, mixed given.    
         🪪  argument.type                                                                                                   
  :100   Cannot access offset 'team_foreign_key' on mixed.                                                                   
         🪪  offsetAccess.nonOffsetAccessible                                                                                
  :100   Parameter #1 $column of method Illuminate\Database\Schema\Blueprint::unsignedBigInteger() expects string, mixed     
         given.                                                                                                              
         🪪  argument.type                                                                                                   
  :101   Cannot access offset 'team_foreign_key' on mixed.                                                                   
         🪪  offsetAccess.nonOffsetAccessible                                                                                
  :101   Parameter #1 $columns of method Illuminate\Database\Schema\Blueprint::index() expects array|string, mixed given.    
         🪪  argument.type                                                                                                   
  :103   Cannot access offset 'model_morph_key' on mixed.                                                                    
         🪪  offsetAccess.nonOffsetAccessible                                                                                
  :103   Cannot access offset 'team_foreign_key' on mixed.                                                                   
         🪪  offsetAccess.nonOffsetAccessible                                                                                
  :106   Cannot access offset 'model_morph_key' on mixed.                                                                    
         🪪  offsetAccess.nonOffsetAccessible                                                                                
  :112   Cannot access offset 'role_has_permissions' on mixed.                                                               
         🪪  offsetAccess.nonOffsetAccessible                                                                                
  :112   Parameter #1 $table of static method Illuminate\Support\Facades\Schema::hasTable() expects string, mixed given.     
         🪪  argument.type                                                                                                   
  :113   Cannot access offset 'role_has_permissions' on mixed.                                                               
         🪪  offsetAccess.nonOffsetAccessible                                                                                
  :113   Parameter #1 $table of static method Illuminate\Support\Facades\Schema::create() expects string, mixed given.       
         🪪  argument.type                                                                                                   
  :114   Parameter #1 $column of method Illuminate\Database\Schema\Blueprint::uuid() expects string, mixed given.            
         🪪  argument.type                                                                                                   
  :115   Parameter #1 $column of method Illuminate\Database\Schema\Blueprint::uuid() expects string, mixed given.            
         🪪  argument.type                                                                                                   
  :117   Parameter #1 $columns of method Illuminate\Database\Schema\Blueprint::foreign() expects array|string, mixed given.  
         🪪  argument.type                                                                                                   
  :119   Cannot access offset 'permissions' on mixed.                                                                        
         🪪  offsetAccess.nonOffsetAccessible                                                                                
  :119   Parameter #1 $table of method Illuminate\Database\Schema\ForeignKeyDefinition::on() expects string, mixed given.    
         🪪  argument.type                                                                                                   
  :122   Parameter #1 $columns of method Illuminate\Database\Schema\Blueprint::foreign() expects array|string, mixed given.  
         🪪  argument.type                                                                                                   
  :124   Cannot access offset 'roles' on mixed.                                                                              
         🪪  offsetAccess.nonOffsetAccessible                                                                                
  :124   Parameter #1 $table of method Illuminate\Database\Schema\ForeignKeyDefinition::on() expects string, mixed given.    
         🪪  argument.type                                                                                                   
  :132   Parameter #1 $name of method Illuminate\Cache\CacheManager::store() expects string|null, mixed given.               
         🪪  argument.type                                                                                                   
  :133   Parameter #1 $key of method Illuminate\Contracts\Cache\Repository::forget() expects string, mixed given.            
         🪪  argument.type                                                                                                   
  :145   Cannot access offset 'role_has_permissions' on mixed.                                                               
         🪪  offsetAccess.nonOffsetAccessible                                                                                
  :145   Parameter #1 $table of static method Illuminate\Support\Facades\Schema::dropIfExists() expects string, mixed        
         given.                                                                                                              
         🪪  argument.type                                                                                                   
  :146   Cannot access offset 'model_has_roles' on mixed.                                                                    
         🪪  offsetAccess.nonOffsetAccessible                                                                                
  :146   Parameter #1 $table of static method Illuminate\Support\Facades\Schema::dropIfExists() expects string, mixed        
         given.                                                                                                              
         🪪  argument.type                                                                                                   
  :147   Cannot access offset 'model_has_permissions' on mixed.                                                              
         🪪  offsetAccess.nonOffsetAccessible                                                                                
  :147   Parameter #1 $table of static method Illuminate\Support\Facades\Schema::dropIfExists() expects string, mixed        
         given.                                                                                                              
         🪪  argument.type                                                                                                   
  :148   Cannot access offset 'roles' on mixed.                                                                              
         🪪  offsetAccess.nonOffsetAccessible                                                                                
  :148   Parameter #1 $table of static method Illuminate\Support\Facades\Schema::dropIfExists() expects string, mixed        
         given.                                                                                                              
         🪪  argument.type                                                                                                   
  :149   Cannot access offset 'permissions' on mixed.                                                                        
         🪪  offsetAccess.nonOffsetAccessible                                                                                
  :149   Parameter #1 $table of static method Illuminate\Support\Facades\Schema::dropIfExists() expects string, mixed        
         given.                                                                                                              
         🪪  argument.type                                                                                                   



  Line   database\migrations\2026_03_13_120000_create_triage_records_table.php                   

  :22    Call to an undefined method Illuminate\Database\Schema\ForeignKeyDefinition::unique().  
         🪪  method.notFound                                                                     



  Line   database\migrations\2026_03_14_120000_create_consultations_table.php                    

  :18    Call to an undefined method Illuminate\Database\Schema\ForeignKeyDefinition::unique().  
         🪪  method.notFound                                                                     



  Line   database\migrations\2026_03_15_090000_create_lab_test_catalogs_table.php  

  :28    Cannot call method uuid() on mixed.                                       
         🪪  method.nonObject                                                      
  :28    Cannot cast mixed to string.                                              
         🪪  cast.string                                                           
  :37    Cannot call method uuid() on mixed.                                       
         🪪  method.nonObject                                                      
  :37    Cannot cast mixed to string.                                              
         🪪  cast.string                                                           
  :46    Cannot call method uuid() on mixed.                                       
         🪪  method.nonObject                                                      
  :46    Cannot cast mixed to string.                                              
         🪪  cast.string                                                           
  :55    Cannot call method uuid() on mixed.                                       
         🪪  method.nonObject                                                      
  :55    Cannot cast mixed to string.                                              
         🪪  cast.string                                                           
  :64    Cannot call method uuid() on mixed.                                       
         🪪  method.nonObject                                                      
  :64    Cannot cast mixed to string.                                              
         🪪  cast.string                                                           
  :89    Cannot call method uuid() on mixed.                                       
         🪪  method.nonObject                                                      
  :89    Cannot cast mixed to string.                                              
         🪪  cast.string                                                           
  :98    Cannot call method uuid() on mixed.                                       
         🪪  method.nonObject                                                      
  :98    Cannot cast mixed to string.                                              
         🪪  cast.string                                                           
  :107   Cannot call method uuid() on mixed.                                       
         🪪  method.nonObject                                                      
  :107   Cannot cast mixed to string.                                              
         🪪  cast.string                                                           
  :116   Cannot call method uuid() on mixed.                                       
         🪪  method.nonObject                                                      
  :116   Cannot cast mixed to string.                                              
         🪪  cast.string                                                           
  :125   Cannot call method uuid() on mixed.                                       
         🪪  method.nonObject                                                      
  :125   Cannot cast mixed to string.                                              
         🪪  cast.string                                                           
  :151   Cannot call method uuid() on mixed.                                       
         🪪  method.nonObject                                                      
  :151   Cannot cast mixed to string.                                              
         🪪  cast.string                                                           
  :161   Cannot call method uuid() on mixed.                                       
         🪪  method.nonObject                                                      
  :161   Cannot cast mixed to string.                                              
         🪪  cast.string                                                           
  :171   Cannot call method uuid() on mixed.                                       
         🪪  method.nonObject                                                      
  :171   Cannot cast mixed to string.                                              
         🪪  cast.string                                                           
  :181   Cannot call method uuid() on mixed.                                       
         🪪  method.nonObject                                                      
  :181   Cannot cast mixed to string.                                              
         🪪  cast.string                                                           



  Line   database\seeders\AddressSeeder.php                      

  :35    Cannot access property $id on App\Models\Country|null.  
         🪪  property.nonObject                                  
  :36    Cannot access property $id on App\Models\Country|null.  
         🪪  property.nonObject                                  
  :37    Cannot access property $id on App\Models\Country|null.  
         🪪  property.nonObject                                  
  :38    Cannot access property $id on App\Models\Country|null.  
         🪪  property.nonObject                                  
  :39    Cannot access property $id on App\Models\Country|null.  
         🪪  property.nonObject                                  
  :40    Cannot access property $id on App\Models\Country|null.  
         🪪  property.nonObject                                  
  :41    Cannot access property $id on App\Models\Country|null.  
         🪪  property.nonObject                                  
  :42    Cannot access property $id on App\Models\Country|null.  
         🪪  property.nonObject                                  
  :43    Cannot access property $id on App\Models\Country|null.  
         🪪  property.nonObject                                  
  :44    Cannot access property $id on App\Models\Country|null.  
         🪪  property.nonObject                                  
  :45    Cannot access property $id on App\Models\Country|null.  
         🪪  property.nonObject                                  
  :46    Cannot access property $id on App\Models\Country|null.  
         🪪  property.nonObject                                  
  :47    Cannot access property $id on App\Models\Country|null.  
         🪪  property.nonObject                                  
  :48    Cannot access property $id on App\Models\Country|null.  
         🪪  property.nonObject                                  
  :49    Cannot access property $id on App\Models\Country|null.  
         🪪  property.nonObject                                  
  :50    Cannot access property $id on App\Models\Country|null.  
         🪪  property.nonObject                                  
  :51    Cannot access property $id on App\Models\Country|null.  
         🪪  property.nonObject                                  
  :52    Cannot access property $id on App\Models\Country|null.  
         🪪  property.nonObject                                  
  :53    Cannot access property $id on App\Models\Country|null.  
         🪪  property.nonObject                                  
  :54    Cannot access property $id on App\Models\Country|null.  
         🪪  property.nonObject                                  



  Line   database\seeders\CityGeneralHospitalEncounterSeeder.php                                                             

  :129   Cannot access offset 'patient_number' on mixed.                                                                     
         🪪  offsetAccess.nonOffsetAccessible                                                                                
  :129   Parameter #1 $key of method Illuminate\Support\Collection<(int|string),App\Models\Patient>::get() expects           
         int|string|null, mixed given.                                                                                       
         🪪  argument.type                                                                                                   
  :130   Cannot access offset 'branch_code' on mixed.                                                                        
         🪪  offsetAccess.nonOffsetAccessible                                                                                
  :130   Parameter #1 $key of method Illuminate\Support\Collection<(int|string),App\Models\FacilityBranch>::get() expects    
         int|string|null, mixed given.                                                                                       
         🪪  argument.type                                                                                                   
  :131   Cannot access offset 'clinic_code' on mixed.                                                                        
         🪪  offsetAccess.nonOffsetAccessible                                                                                
  :131   Parameter #1 $key of method Illuminate\Support\Collection<(int|string),App\Models\Clinic>::get() expects            
         int|string|null, mixed given.                                                                                       
         🪪  argument.type                                                                                                   
  :132   Cannot access offset 'doctor_email' on mixed.                                                                       
         🪪  offsetAccess.nonOffsetAccessible                                                                                
  :132   Parameter #1 $key of method Illuminate\Support\Collection<(int|string),App\Models\Staff>::get() expects             
         int|string|null, mixed given.                                                                                       
         🪪  argument.type                                                                                                   
  :152   Cannot access offset 'visit_number' on mixed.                                                                       
         🪪  offsetAccess.nonOffsetAccessible                                                                                
  :159   Cannot access offset 'visit_type' on mixed.                                                                         
         🪪  offsetAccess.nonOffsetAccessible                                                                                
  :159   Cannot access property $value on mixed.                                                                             
         🪪  property.nonObject                                                                                              
  :160   Cannot access offset 'status' on mixed.                                                                             
         🪪  offsetAccess.nonOffsetAccessible                                                                                
  :160   Cannot access property $value on mixed.                                                                             
         🪪  property.nonObject                                                                                              
  :161   Cannot access offset 'is_emergency' on mixed.                                                                       
         🪪  offsetAccess.nonOffsetAccessible                                                                                
  :162   Cannot access offset 'notes' on mixed.                                                                              
         🪪  offsetAccess.nonOffsetAccessible                                                                                
  :163   Cannot access offset 'registered_at' on mixed.                                                                      
         🪪  offsetAccess.nonOffsetAccessible                                                                                
  :164   Cannot access offset 'started_at' on mixed.                                                                         
         🪪  offsetAccess.nonOffsetAccessible                                                                                
  :165   Cannot access offset 'completed_at' on mixed.                                                                       
         🪪  offsetAccess.nonOffsetAccessible                                                                                
  :189   Cannot access offset 'invoice_number' on mixed.                                                                     
         🪪  offsetAccess.nonOffsetAccessible                                                                                
  :196   Cannot access offset 'consultation' on mixed.                                                                       
         🪪  offsetAccess.nonOffsetAccessible                                                                                
  :196   Parameter #5 $consultationData of method Database\Seeders\CityGeneralHospitalEncounterSeeder::syncConsultation()    
         expects array|null, mixed given.                                                                                    
         🪪  argument.type                                                                                                   
  :198   Cannot access offset 'lab_request' on mixed.                                                                        
         🪪  offsetAccess.nonOffsetAccessible                                                                                
  :205   Cannot access offset 'workflow_staff_email' on mixed.                                                               
         🪪  offsetAccess.nonOffsetAccessible                                                                                
  :205   Parameter #2 $email of method Database\Seeders\CityGeneralHospitalEncounterSeeder::requireStaff() expects string,   
         mixed given.                                                                                                        
         🪪  argument.type                                                                                                   
  :207   Parameter #8 $requestData of method Database\Seeders\CityGeneralHospitalEncounterSeeder::syncLabRequest() expects   
         array, mixed given.                                                                                                 
         🪪  argument.type                                                                                                   
  :211   Argument of an invalid type mixed supplied for foreach, only iterables are supported.                               
         🪪  foreach.nonIterable                                                                                             
  :211   Cannot access offset 'service_orders' on mixed.                                                                     
         🪪  offsetAccess.nonOffsetAccessible                                                                                
  :218   Cannot access offset 'performed_by_email' on mixed.                                                                 
         🪪  offsetAccess.nonOffsetAccessible                                                                                
  :218   Parameter #2 $email of method Database\Seeders\CityGeneralHospitalEncounterSeeder::requireStaff() expects string,   
         mixed given.                                                                                                        
         🪪  argument.type                                                                                                   
  :219   Cannot access offset 'service_code' on mixed.                                                                       
         🪪  offsetAccess.nonOffsetAccessible                                                                                
  :219   Parameter #2 $serviceCode of method Database\Seeders\CityGeneralHospitalEncounterSeeder::requireService() expects   
         string, mixed given.                                                                                                
         🪪  argument.type                                                                                                   
  :220   Parameter #8 $orderData of method Database\Seeders\CityGeneralHospitalEncounterSeeder::syncFacilityServiceOrder()   
         expects array, mixed given.                                                                                         
         🪪  argument.type                                                                                                   
  :225   Cannot access offset 'payment' on mixed.                                                                            
         🪪  offsetAccess.nonOffsetAccessible                                                                                
  :225   Parameter #6 $paymentData of method Database\Seeders\CityGeneralHospitalEncounterSeeder::syncPayment() expects      
         array|null, mixed given.                                                                                            
         🪪  argument.type                                                                                                   
  :272   Method Database\Seeders\CityGeneralHospitalEncounterSeeder::syncConsultation() has parameter $consultationData      
         with no value type specified in iterable type array.                                                                
         🪪  missingType.iterableValue                                                                                       
         💡  See: https://phpstan.org/blog/solving-phpstan-no-value-type-specified-in-iterable-type                          
  :298   Cannot access property $value on mixed.                                                                             
         🪪  property.nonObject                                                                                              
  :306   Method Database\Seeders\CityGeneralHospitalEncounterSeeder::syncLabRequest() has parameter $requestData with no     
         value type specified in iterable type array.                                                                        
         🪪  missingType.iterableValue                                                                                       
         💡  See: https://phpstan.org/blog/solving-phpstan-no-value-type-specified-in-iterable-type                          
  :306   Method Database\Seeders\CityGeneralHospitalEncounterSeeder::syncLabRequest() has parameter $tests with generic      
         class Illuminate\Support\Collection but does not specify its types: TKey, TValue                                    
         🪪  missingType.generics                                                                                            
  :327   Cannot access property $value on mixed.                                                                             
         🪪  property.nonObject                                                                                              
  :328   Cannot access property $value on mixed.                                                                             
         🪪  property.nonObject                                                                                              
  :331   Cannot access property $value on mixed.                                                                             
         🪪  property.nonObject                                                                                              
  :333   Parameter #1 $value of function collect expects Illuminate\Contracts\Support\Arrayable<(int|string),                
         mixed>|iterable<(int|string), mixed>|null, mixed given.                                                             
         🪪  argument.type                                                                                                   
  :333   Unable to resolve the template type TKey in call to function collect                                                
         🪪  argument.templateType                                                                                           
         💡  See: https://phpstan.org/blog/solving-phpstan-error-unable-to-resolve-template-type                             
  :333   Unable to resolve the template type TValue in call to function collect                                              
         🪪  argument.templateType                                                                                           
         💡  See: https://phpstan.org/blog/solving-phpstan-error-unable-to-resolve-template-type                             
  :338   Argument of an invalid type mixed supplied for foreach, only iterables are supported.                               
         🪪  foreach.nonIterable                                                                                             
  :339   Cannot access offset 'test_code' on mixed.                                                                          
         🪪  offsetAccess.nonOffsetAccessible                                                                                
  :339   Parameter #2 $testCode of method Database\Seeders\CityGeneralHospitalEncounterSeeder::requireTest() expects         
         string, mixed given.                                                                                                
         🪪  argument.type                                                                                                   
  :347   Cannot access offset 'status' on mixed.                                                                             
         🪪  offsetAccess.nonOffsetAccessible                                                                                
  :347   Cannot access property $value on mixed.                                                                             
         🪪  property.nonObject                                                                                              
  :350   Cannot access offset 'status' on mixed.                                                                             
         🪪  offsetAccess.nonOffsetAccessible                                                                                
  :351   Cannot call method addMinutes() on mixed.                                                                           
         🪪  method.nonObject                                                                                                
  :351   Cannot call method copy() on mixed.                                                                                 
         🪪  method.nonObject                                                                                                
  :352   Cannot access offset 'result' on mixed.                                                                             
         🪪  offsetAccess.nonOffsetAccessible                                                                                
  :353   Cannot access offset 'completed_at' on mixed.                                                                       
         🪪  offsetAccess.nonOffsetAccessible                                                                                
  :353   Cannot call method copy() on mixed.                                                                                 
         🪪  method.nonObject                                                                                                
  :353   Cannot call method subMinutes() on mixed.                                                                           
         🪪  method.nonObject                                                                                                
  :355   Cannot access offset 'completed_at' on mixed.                                                                       
         🪪  offsetAccess.nonOffsetAccessible                                                                                
  :355   Cannot call method copy() on mixed.                                                                                 
         🪪  method.nonObject                                                                                                
  :355   Cannot call method subMinutes() on mixed.                                                                           
         🪪  method.nonObject                                                                                                
  :357   Cannot access offset 'completed_at' on mixed.                                                                       
         🪪  offsetAccess.nonOffsetAccessible                                                                                
  :358   Cannot access offset 'completed_at' on mixed.                                                                       
         🪪  offsetAccess.nonOffsetAccessible                                                                                
  :363   Parameter #3 $resultData of method Database\Seeders\CityGeneralHospitalEncounterSeeder::syncLabResultEntry()        
         expects array, mixed given.                                                                                         
         🪪  argument.type                                                                                                   
  :370   Method Database\Seeders\CityGeneralHospitalEncounterSeeder::syncLabResultEntry() has parameter $resultData with no  
         value type specified in iterable type array.                                                                        
         🪪  missingType.iterableValue                                                                                       
         💡  See: https://phpstan.org/blog/solving-phpstan-no-value-type-specified-in-iterable-type                          
  :393   Argument of an invalid type mixed supplied for foreach, only iterables are supported.                               
         🪪  foreach.nonIterable                                                                                             
  :394   Cannot access offset 'label' on mixed.                                                                              
         🪪  offsetAccess.nonOffsetAccessible                                                                                
  :394   Parameter #1 $key of method Illuminate\Support\Collection<(int|string),mixed>::get() expects int|string|null,       
         mixed given.                                                                                                        
         🪪  argument.type                                                                                                   
  :395   Cannot access offset 'value' on mixed.                                                                              
         🪪  offsetAccess.nonOffsetAccessible                                                                                
  :400   Cannot access offset 'label' on mixed.                                                                              
         🪪  offsetAccess.nonOffsetAccessible                                                                                
  :403   Cannot access property $id on mixed.                                                                                
         🪪  property.nonObject                                                                                              
  :405   Cannot cast mixed to string.                                                                                        
         🪪  cast.string                                                                                                     
  :406   Cannot access property $unit on mixed.                                                                              
         🪪  property.nonObject                                                                                              
  :407   Cannot access property $reference_range on mixed.                                                                   
         🪪  property.nonObject                                                                                              
  :408   Binary operation "+" between mixed and 1 results in an error.                                                       
         🪪  binaryOp.invalid                                                                                                
  :414   Method Database\Seeders\CityGeneralHospitalEncounterSeeder::syncFacilityServiceOrder() has parameter $orderData     
         with no value type specified in iterable type array.                                                                
         🪪  missingType.iterableValue                                                                                       
         💡  See: https://phpstan.org/blog/solving-phpstan-no-value-type-specified-in-iterable-type                          
  :434   Cannot access property $value on mixed.                                                                             
         🪪  property.nonObject                                                                                              
  :446   Method Database\Seeders\CityGeneralHospitalEncounterSeeder::syncPayment() has parameter $paymentData with no value  
         type specified in iterable type array.                                                                              
         🪪  missingType.iterableValue                                                                                       
         💡  See: https://phpstan.org/blog/solving-phpstan-no-value-type-specified-in-iterable-type                          
  :476   Method Database\Seeders\CityGeneralHospitalEncounterSeeder::encounterBlueprints() return type has no value type     
         specified in iterable type array.                                                                                   
         🪪  missingType.iterableValue                                                                                       
         💡  See: https://phpstan.org/blog/solving-phpstan-no-value-type-specified-in-iterable-type                          



  Line   database\seeders\CityGeneralHospitalInventoryWorkflowSeeder.php                                                    

  :64    Parameter $locations of method Database\Seeders\CityGeneralHospitalInventoryWorkflowSeeder::seedReceiptWorkflow()  
         expects array<string, App\Models\InventoryLocation>, array<App\Models\InventoryLocation> given.                    
         🪪  argument.type                                                                                                  
  :72    Parameter $locations of method Database\Seeders\CityGeneralHospitalInventoryWorkflowSeeder::seedReconciliations()  
         expects array<string, App\Models\InventoryLocation>, array<App\Models\InventoryLocation> given.                    
         🪪  argument.type                                                                                                  
  :80    Parameter $locations of method Database\Seeders\CityGeneralHospitalInventoryWorkflowSeeder::seedRequisitions()     
         expects array<string, App\Models\InventoryLocation>, array<App\Models\InventoryLocation> given.                    
         🪪  argument.type                                                                                                  



  Line   database\seeders\ClinicSeeder.php                          

  :33    Cannot access property $id on App\Models\Department|null.  
         🪪  property.nonObject                                     
  :40    Cannot access property $id on App\Models\Department|null.  
         🪪  property.nonObject                                     
  :47    Cannot access property $id on App\Models\Department|null.  
         🪪  property.nonObject                                     



  Line   database\seeders\FacilitySeeder.php                                                                                 

  :49    Parameter #1 $key of method Illuminate\Support\Collection<(int|string),App\Models\Country>::get() expects           
         int|string|null, mixed given.                                                                                       
         🪪  argument.type                                                                                                   
  :52    Parameter #1 $key of method Illuminate\Support\Collection<(int|string),App\Models\Currency>::get() expects          
         int|string|null, mixed given.                                                                                       
         🪪  argument.type                                                                                                   
  :54    Parameter #1 $address of method Database\Seeders\FacilitySeeder::upsertAddress() expects array{city: string,        
         district: string, state: string}, mixed given.                                                                      
         🪪  argument.type                                                                                                   
  :65    Cannot access property $value on mixed.                                                                             
         🪪  property.nonObject                                                                                              
  :73    Parameter #3 $subscription of method Database\Seeders\FacilitySeeder::upsertSubscription() expects array{status:    
         App\Enums\SubscriptionStatus, starts_at: Illuminate\Support\Carbon, trial_ends_at: Illuminate\Support\Carbon|null,  
         activated_at: Illuminate\Support\Carbon|null, current_period_starts_at: Illuminate\Support\Carbon|null,             
         current_period_ends_at: Illuminate\Support\Carbon|null, meta: array<string, mixed>}, mixed given.                   
         🪪  argument.type                                                                                                   
  :75    Argument of an invalid type mixed supplied for foreach, only iterables are supported.                               
         🪪  foreach.nonIterable                                                                                             
  :76    Cannot access offset 'address' on mixed.                                                                            
         🪪  offsetAccess.nonOffsetAccessible                                                                                
  :76    Parameter #1 $address of method Database\Seeders\FacilitySeeder::upsertAddress() expects array{city: string,        
         district: string, state: string}, mixed given.                                                                      
         🪪  argument.type                                                                                                   
  :81    Cannot access offset 'branch_code' on mixed.                                                                        
         🪪  offsetAccess.nonOffsetAccessible                                                                                
  :84    Cannot access offset 'name' on mixed.                                                                               
         🪪  offsetAccess.nonOffsetAccessible                                                                                
  :88    Cannot access offset 'is_main_branch' on mixed.                                                                     
         🪪  offsetAccess.nonOffsetAccessible                                                                                
  :89    Cannot access offset 'has_store' on mixed.                                                                          
         🪪  offsetAccess.nonOffsetAccessible                                                                                
  :90    Cannot access offset 'main_contact' on mixed.                                                                       
         🪪  offsetAccess.nonOffsetAccessible                                                                                
  :91    Cannot access offset 'other_contact' on mixed.                                                                      
         🪪  offsetAccess.nonOffsetAccessible                                                                                
  :92    Cannot access offset 'email' on mixed.                                                                              
         🪪  offsetAccess.nonOffsetAccessible                                                                                



  Line   database\seeders\PatientSeeder.php                                                                                  

  :67    Using nullsafe property access on non-nullable type                                                                 
         App\Enums\Religion::CHRISTIAN|App\Enums\Religion::MUSLIM|App\Enums\Religion::OTHER. Use -> instead.                 
         🪪  nullsafe.neverNull                                                                                              
  :68    Using nullsafe property access on non-nullable type                                                                 
         App\Enums\BloodGroup::A_NEGATIVE|App\Enums\BloodGroup::A_POSITIVE|App\Enums\BloodGroup::AB_NEGATIVE|App\Enums\Bloo  
         dGroup::AB_POSITIVE|App\Enums\BloodGroup::B_NEGATIVE|App\Enums\BloodGroup::B_POSITIVE|App\Enums\BloodGroup::O_NEGA  
         TIVE|App\Enums\BloodGroup::O_POSITIVE. Use -> instead.                                                              
         🪪  nullsafe.neverNull                                                                                              
  :69    Offset 'next_of_kin_name' on array{patient_number: 'PAT-0001', first_name: 'Amara', last_name: 'Nakigozi',          
         middle_name: 'Grace', date_of_birth: '1990-05-14', gender: App\Enums\Gender::FEMALE, phone_number:                  
         '+256-700-100-001', email: 'amara.nakigozi…', ...}|array{patient_number: 'PAT-0002', first_name: 'Brian',           
         last_name: 'Ochieng', middle_name: null, date_of_birth: '1985-08-22', gender: App\Enums\Gender::MALE,               
         phone_number: '+256-700-100-002', email: null, ...}|array{patient_number: 'PAT-0003', first_name: 'Fatuma',         
         last_name: 'Namusisi', middle_name: 'Zainab', date_of_birth: '1975-02-10', gender: App\Enums\Gender::FEMALE,        
         phone_number: '+256-700-100-003', email: 'fatuma.namusisi…', ...}|array{patient_number: 'PAT-0004', first_name:     
         'Charles', last_name: 'Ssekandi', middle_name: 'Patrick', date_of_birth: '1960-11-30', gender:                      
         App\Enums\Gender::MALE, phone_number: '+256-700-100-004', email: null, ...}|array{patient_number: 'PAT-0005',       
         first_name: 'Diana', last_name: 'Akello', middle_name: null, date_of_birth: '2000-03-18', gender:                   
         App\Enums\Gender::FEMALE, phone_number: '+256-700-100-005', email: 'diana.akello…', ...}|array{patient_number:      
         'PAT-0006', first_name: 'George', last_name: 'Tumwine', middle_name: 'Robert', date_of_birth: '1978-07-04',         
         gender: App\Enums\Gender::MALE, phone_number: '+256-700-100-006', email: null, ...}|array{patient_number:           
         'PAT-0007', first_name: 'Hawa', last_name: 'Nantume', middle_name: 'Khadija', date_of_birth: '1993-12-01', gender:  
         App\Enums\Gender::FEMALE, phone_number: '+256-700-100-007', email: 'hawa.nantume…', ...}|array{patient_number:      
         'PAT-0008', first_name: 'Isaac', last_name: 'Wanyama', middle_name: null, date_of_birth: '2015-06-09', gender:      
         App\Enums\Gender::MALE, phone_number: '+256-700-100-008', email: null, ...}|array{patient_number: 'PAT-0009',       
         first_name: 'Josephine', last_name: 'Atim', middle_name: 'Mary', date_of_birth: '1968-04-25', gender:               
         App\Enums\Gender::FEMALE, phone_number: '+256-700-100-009', email: null, ...}|array{patient_number: 'PAT-0010',     
         first_name: 'Kenneth', last_name: 'Byamugisha', middle_name: 'Joel', date_of_birth: '1955-09-17', gender:           
         App\Enums\Gender::MALE, phone_number: '+256-700-100-010', email: null, ...} on left side of ?? always exists and    
         is not nullable.                                                                                                    
         🪪  nullCoalesce.offset                                                                                             
  :70    Offset 'next_of_kin_phone' on array{patient_number: 'PAT-0001', first_name: 'Amara', last_name: 'Nakigozi',         
         middle_name: 'Grace', date_of_birth: '1990-05-14', gender: App\Enums\Gender::FEMALE, phone_number:                  
         '+256-700-100-001', email: 'amara.nakigozi…', ...}|array{patient_number: 'PAT-0002', first_name: 'Brian',           
         last_name: 'Ochieng', middle_name: null, date_of_birth: '1985-08-22', gender: App\Enums\Gender::MALE,               
         phone_number: '+256-700-100-002', email: null, ...}|array{patient_number: 'PAT-0003', first_name: 'Fatuma',         
         last_name: 'Namusisi', middle_name: 'Zainab', date_of_birth: '1975-02-10', gender: App\Enums\Gender::FEMALE,        
         phone_number: '+256-700-100-003', email: 'fatuma.namusisi…', ...}|array{patient_number: 'PAT-0004', first_name:     
         'Charles', last_name: 'Ssekandi', middle_name: 'Patrick', date_of_birth: '1960-11-30', gender:                      
         App\Enums\Gender::MALE, phone_number: '+256-700-100-004', email: null, ...}|array{patient_number: 'PAT-0005',       
         first_name: 'Diana', last_name: 'Akello', middle_name: null, date_of_birth: '2000-03-18', gender:                   
         App\Enums\Gender::FEMALE, phone_number: '+256-700-100-005', email: 'diana.akello…', ...}|array{patient_number:      
         'PAT-0006', first_name: 'George', last_name: 'Tumwine', middle_name: 'Robert', date_of_birth: '1978-07-04',         
         gender: App\Enums\Gender::MALE, phone_number: '+256-700-100-006', email: null, ...}|array{patient_number:           
         'PAT-0007', first_name: 'Hawa', last_name: 'Nantume', middle_name: 'Khadija', date_of_birth: '1993-12-01', gender:  
         App\Enums\Gender::FEMALE, phone_number: '+256-700-100-007', email: 'hawa.nantume…', ...}|array{patient_number:      
         'PAT-0008', first_name: 'Isaac', last_name: 'Wanyama', middle_name: null, date_of_birth: '2015-06-09', gender:      
         App\Enums\Gender::MALE, phone_number: '+256-700-100-008', email: null, ...}|array{patient_number: 'PAT-0009',       
         first_name: 'Josephine', last_name: 'Atim', middle_name: 'Mary', date_of_birth: '1968-04-25', gender:               
         App\Enums\Gender::FEMALE, phone_number: '+256-700-100-009', email: null, ...}|array{patient_number: 'PAT-0010',     
         first_name: 'Kenneth', last_name: 'Byamugisha', middle_name: 'Joel', date_of_birth: '1955-09-17', gender:           
         App\Enums\Gender::MALE, phone_number: '+256-700-100-010', email: null, ...} on left side of ?? always exists and    
         is not nullable.                                                                                                    
         🪪  nullCoalesce.offset                                                                                             
  :71    Using nullsafe property access on non-nullable type                                                                 
         App\Enums\KinRelationship::CHILD|App\Enums\KinRelationship::PARENT|App\Enums\KinRelationship::SIBLING|App\Enums\Ki  
         nRelationship::SPOUSE. Use -> instead.                                                                              
         🪪  nullsafe.neverNull                                                                                              
  :72    Cannot access property $id on App\Models\Address|null.                                                              
         🪪  property.nonObject                                                                                              



  Line   database\seeders\PermissionSeeder.php                                                                               

  :199   Method Database\Seeders\PermissionSeeder::expandPermissions() should return list<string> but returns array<int, no  
         n-falsy-string>.                                                                                                    
         🪪  return.type                                                                                                     
         💡  array<int, non-falsy-string> might not be a list.                                                               



  Line   database\seeders\StaffSeeder.php                                                                                    

  :254   Offset 'middle_name' on array{employee_number: 'FIN-001', first_name: 'Stella', last_name: 'Senteza', middle_name:  
         'Rose', email: 'stella.senteza…', phone: '+256-702-111-012', department_name: 'Finance', position_name: 'Finance    
         Officer', ...}|array{employee_number: 'HAD-001', first_name: 'Michael', last_name: 'Wakiro', middle_name: 'David',  
         email: 'michael.wakiro…', phone: '+256-702-111-011', department_name: 'Administration', position_name:              
         'Healthcare…', ...}|array{employee_number: 'HR-001', first_name: 'Vincent', last_name: 'Kyeyune', middle_name:      
         'Andrew', email: 'vincent.kyeyune…', phone: '+256-702-111-013', department_name: 'Human Resources', position_name:  
         'Human Resources…', ...}|array{employee_number: 'IT-001', first_name: 'Richard', last_name: 'Owino', middle_name:   
         'Stephen', email: 'richard.owino…', phone: '+256-702-111-014', department_name: 'Administration', position_name:    
         'IT Support Officer', ...}|array{employee_number: 'LAB-001', first_name: 'David', last_name: 'Ssemanda',            
         middle_name: 'Leon', email: 'david.ssemanda…', phone: '+256-702-111-009', department_name: 'Laboratory',            
         position_name: 'Laboratory…', ...}|array{employee_number: 'MED-001', first_name: 'Dr. John', last_name: 'Okonkwo',  
         middle_name: 'James', email: 'john.okonkwo…', phone: '+256-702-111-001', department_name: 'Cardiology',             
         position_name: 'Consultant Doctor', ...}|array{employee_number: 'MED-002', first_name: 'Dr. Sarah', last_name:      
         'Mutesi', middle_name: 'Anne', email: 'sarah.mutesi…', phone: '+256-702-111-002', department_name: 'Orthopedics',   
         position_name: 'Doctor', ...}|array{employee_number: 'MED-003', first_name: 'Dr. Robert', last_name: 'Kato',        
         middle_name: 'Paul', email: 'robert.kato…', phone: '+256-702-111-003', department_name: 'Pediatrics',               
         position_name: 'Consultant Doctor', ...}|array{employee_number: 'MED-004', first_name: 'Dr. Mary', last_name:       
         'Namayanja', middle_name: 'Grace', email: 'mary.namayanja…', phone: '+256-702-111-004', department_name: 'Internal  
         Medicine', position_name: 'Doctor', ...}|array{employee_number: 'NUR-001', first_name: 'Jane', last_name: 'Apio',   
         middle_name: 'Catherine', email: 'jane.apio@hospital…', phone: '+256-702-111-006', department_name: 'Nursing',      
         position_name: 'Head Nurse', ...}|array{employee_number: 'NUR-002', first_name: 'Peter', last_name: 'Okina',        
         middle_name: 'Victor', email: 'peter.okina…', phone: '+256-702-111-007', department_name: 'Nursing',                
         position_name: 'Senior Nurse', ...}|array{employee_number: 'NUR-003', first_name: 'Alice', last_name: 'Mukwaya',    
         middle_name: 'Beatrice', email: 'alice.mukwaya…', phone: '+256-702-111-008', department_name: 'Nursing',            
         position_name: 'Registered Nurse', ...}|array{employee_number: 'RAD-001', first_name: 'Elizabeth', last_name:       
         'Nabugumira', middle_name: 'Joan', email: 'elizabeth…', phone: '+256-702-111-010', department_name: 'Radiology',    
         position_name: 'Radiographer', ...}|array{employee_number: 'SUR-001', first_name: 'Dr. James', last_name:           
         'Kyambire', middle_name: 'Moses', email: 'james.kyambire…', phone: '+256-702-111-005', department_name: 'Surgery',  
         position_name: 'Consultant Doctor', ...} on left side of ?? always exists and is not nullable.                      
         🪪  nullCoalesce.offset                                                                                             
  :255   Offset 'phone' on array{employee_number: 'FIN-001', first_name: 'Stella', last_name: 'Senteza', middle_name:        
         'Rose', email: 'stella.senteza…', phone: '+256-702-111-012', department_name: 'Finance', position_name: 'Finance    
         Officer', ...}|array{employee_number: 'HAD-001', first_name: 'Michael', last_name: 'Wakiro', middle_name: 'David',  
         email: 'michael.wakiro…', phone: '+256-702-111-011', department_name: 'Administration', position_name:              
         'Healthcare…', ...}|array{employee_number: 'HR-001', first_name: 'Vincent', last_name: 'Kyeyune', middle_name:      
         'Andrew', email: 'vincent.kyeyune…', phone: '+256-702-111-013', department_name: 'Human Resources', position_name:  
         'Human Resources…', ...}|array{employee_number: 'IT-001', first_name: 'Richard', last_name: 'Owino', middle_name:   
         'Stephen', email: 'richard.owino…', phone: '+256-702-111-014', department_name: 'Administration', position_name:    
         'IT Support Officer', ...}|array{employee_number: 'LAB-001', first_name: 'David', last_name: 'Ssemanda',            
         middle_name: 'Leon', email: 'david.ssemanda…', phone: '+256-702-111-009', department_name: 'Laboratory',            
         position_name: 'Laboratory…', ...}|array{employee_number: 'MED-001', first_name: 'Dr. John', last_name: 'Okonkwo',  
         middle_name: 'James', email: 'john.okonkwo…', phone: '+256-702-111-001', department_name: 'Cardiology',             
         position_name: 'Consultant Doctor', ...}|array{employee_number: 'MED-002', first_name: 'Dr. Sarah', last_name:      
         'Mutesi', middle_name: 'Anne', email: 'sarah.mutesi…', phone: '+256-702-111-002', department_name: 'Orthopedics',   
         position_name: 'Doctor', ...}|array{employee_number: 'MED-003', first_name: 'Dr. Robert', last_name: 'Kato',        
         middle_name: 'Paul', email: 'robert.kato…', phone: '+256-702-111-003', department_name: 'Pediatrics',               
         position_name: 'Consultant Doctor', ...}|array{employee_number: 'MED-004', first_name: 'Dr. Mary', last_name:       
         'Namayanja', middle_name: 'Grace', email: 'mary.namayanja…', phone: '+256-702-111-004', department_name: 'Internal  
         Medicine', position_name: 'Doctor', ...}|array{employee_number: 'NUR-001', first_name: 'Jane', last_name: 'Apio',   
         middle_name: 'Catherine', email: 'jane.apio@hospital…', phone: '+256-702-111-006', department_name: 'Nursing',      
         position_name: 'Head Nurse', ...}|array{employee_number: 'NUR-002', first_name: 'Peter', last_name: 'Okina',        
         middle_name: 'Victor', email: 'peter.okina…', phone: '+256-702-111-007', department_name: 'Nursing',                
         position_name: 'Senior Nurse', ...}|array{employee_number: 'NUR-003', first_name: 'Alice', last_name: 'Mukwaya',    
         middle_name: 'Beatrice', email: 'alice.mukwaya…', phone: '+256-702-111-008', department_name: 'Nursing',            
         position_name: 'Registered Nurse', ...}|array{employee_number: 'RAD-001', first_name: 'Elizabeth', last_name:       
         'Nabugumira', middle_name: 'Joan', email: 'elizabeth…', phone: '+256-702-111-010', department_name: 'Radiology',    
         position_name: 'Radiographer', ...}|array{employee_number: 'SUR-001', first_name: 'Dr. James', last_name:           
         'Kyambire', middle_name: 'Moses', email: 'james.kyambire…', phone: '+256-702-111-005', department_name: 'Surgery',  
         position_name: 'Consultant Doctor', ...} on left side of ?? always exists and is not nullable.                      
         🪪  nullCoalesce.offset                                                                                             
  :256   Cannot access property $id on App\Models\Address|null.                                                              
         🪪  property.nonObject                                                                                              
  :260   Offset 'specialty' on array{employee_number: 'FIN-001', first_name: 'Stella', last_name: 'Senteza', middle_name:    
         'Rose', email: 'stella.senteza…', phone: '+256-702-111-012', department_name: 'Finance', position_name: 'Finance    
         Officer', ...}|array{employee_number: 'HAD-001', first_name: 'Michael', last_name: 'Wakiro', middle_name: 'David',  
         email: 'michael.wakiro…', phone: '+256-702-111-011', department_name: 'Administration', position_name:              
         'Healthcare…', ...}|array{employee_number: 'HR-001', first_name: 'Vincent', last_name: 'Kyeyune', middle_name:      
         'Andrew', email: 'vincent.kyeyune…', phone: '+256-702-111-013', department_name: 'Human Resources', position_name:  
         'Human Resources…', ...}|array{employee_number: 'IT-001', first_name: 'Richard', last_name: 'Owino', middle_name:   
         'Stephen', email: 'richard.owino…', phone: '+256-702-111-014', department_name: 'Administration', position_name:    
         'IT Support Officer', ...}|array{employee_number: 'LAB-001', first_name: 'David', last_name: 'Ssemanda',            
         middle_name: 'Leon', email: 'david.ssemanda…', phone: '+256-702-111-009', department_name: 'Laboratory',            
         position_name: 'Laboratory…', ...}|array{employee_number: 'MED-001', first_name: 'Dr. John', last_name: 'Okonkwo',  
         middle_name: 'James', email: 'john.okonkwo…', phone: '+256-702-111-001', department_name: 'Cardiology',             
         position_name: 'Consultant Doctor', ...}|array{employee_number: 'MED-002', first_name: 'Dr. Sarah', last_name:      
         'Mutesi', middle_name: 'Anne', email: 'sarah.mutesi…', phone: '+256-702-111-002', department_name: 'Orthopedics',   
         position_name: 'Doctor', ...}|array{employee_number: 'MED-003', first_name: 'Dr. Robert', last_name: 'Kato',        
         middle_name: 'Paul', email: 'robert.kato…', phone: '+256-702-111-003', department_name: 'Pediatrics',               
         position_name: 'Consultant Doctor', ...}|array{employee_number: 'MED-004', first_name: 'Dr. Mary', last_name:       
         'Namayanja', middle_name: 'Grace', email: 'mary.namayanja…', phone: '+256-702-111-004', department_name: 'Internal  
         Medicine', position_name: 'Doctor', ...}|array{employee_number: 'NUR-001', first_name: 'Jane', last_name: 'Apio',   
         middle_name: 'Catherine', email: 'jane.apio@hospital…', phone: '+256-702-111-006', department_name: 'Nursing',      
         position_name: 'Head Nurse', ...}|array{employee_number: 'NUR-002', first_name: 'Peter', last_name: 'Okina',        
         middle_name: 'Victor', email: 'peter.okina…', phone: '+256-702-111-007', department_name: 'Nursing',                
         position_name: 'Senior Nurse', ...}|array{employee_number: 'NUR-003', first_name: 'Alice', last_name: 'Mukwaya',    
         middle_name: 'Beatrice', email: 'alice.mukwaya…', phone: '+256-702-111-008', department_name: 'Nursing',            
         position_name: 'Registered Nurse', ...}|array{employee_number: 'RAD-001', first_name: 'Elizabeth', last_name:       
         'Nabugumira', middle_name: 'Joan', email: 'elizabeth…', phone: '+256-702-111-010', department_name: 'Radiology',    
         position_name: 'Radiographer', ...}|array{employee_number: 'SUR-001', first_name: 'Dr. James', last_name:           
         'Kyambire', middle_name: 'Moses', email: 'james.kyambire…', phone: '+256-702-111-005', department_name: 'Surgery',  
         position_name: 'Consultant Doctor', ...} on left side of ?? always exists and is not nullable.                      
         🪪  nullCoalesce.offset                                                                                             



  Line   database\seeders\SupportUserSeeder.php                                             

  :52    Cannot call method orderBy() on mixed.                                             
         🪪  method.nonObject                                                               
  :52    Cannot call method orderByDesc() on mixed.                                         
         🪪  method.nonObject                                                               
  :53    Cannot call method orderBy() on mixed.                                             
         🪪  method.nonObject                                                               
  :119   Parameter #1 $string of function mb_strtoupper expects string, string|null given.  
         🪪  argument.type                                                                  


                                                                                                                        
 [ERROR] Found 968 errors                                                                                               
                                                                                                                        

PS C:\Users\Manoah\Desktop\projects\personal-practice\mini-hospital-v2> 