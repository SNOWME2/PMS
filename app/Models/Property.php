namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Unit;

class Property extends Model
{
protected $fillable = [
'name',
'address',
'description',
'amenities',
];

protected $casts = [
'amenities' => 'array',
];

public function units()
{
return $this->hasMany(Unit::class);
}
}