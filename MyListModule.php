<?php

/**
 * MyList module derived from Example module and MediaListModule.
 */

declare(strict_types=1);

namespace MyListNamespace;

use Fisharebest\Localization\Translation;
use Fisharebest\Webtrees\I18N;
use Fisharebest\Webtrees\Module\AbstractModule;
use Fisharebest\Webtrees\Module\ModuleCustomInterface;
use Fisharebest\Webtrees\Module\ModuleCustomTrait;
use Fisharebest\Webtrees\Module\ModuleListTrait;
use Fisharebest\Webtrees\Module\ModuleListInterface;

use Fisharebest\Webtrees\Auth;
use Fisharebest\Webtrees\GedcomRecord;
use Fisharebest\Webtrees\Registry;
use Fisharebest\Webtrees\Tree;
use Fisharebest\Webtrees\Validator;
use Fisharebest\Webtrees\Repository;
use Fisharebest\Webtrees\Source;
use Fisharebest\Webtrees\Fact;
use Fisharebest\Webtrees\Services\LinkedRecordService;
use Illuminate\Database\Capsule\Manager as DB;
use Fig\Http\Message\RequestMethodInterface;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Database\Query\Expression;
use Illuminate\Support\Collection;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

use function app;
use function response;
use function redirect;
use function route;
use function view;

/**
 * Class MyListModule
 *
 * @author  Ansgar Häger <ansgar@haeger-dechent.de>
 * @license https://opensource.org/licenses/GPL-3.0 GNU General Public License v3.0 
 *
 */
class MyListModule extends AbstractModule implements ModuleCustomInterface, ModuleListInterface, RequestHandlerInterface 
{
	// For every module interface that is implemented, the corresponding trait *should* also use be used.
	use ModuleCustomTrait;
	use ModuleListTrait;


	//protected const ROUTE_URL  = '/tree/{tree}/MyList-{repository}/{xref}';
	protected const ROUTE_URL = '/tree/{tree}/WeEt';

	private LinkedRecordService $linked_record_service;


	/** @var int The default access level for this module.  It can be changed in the control panel. */
	protected int $access_level = Auth::PRIV_USER;


	/**
	 * The constructor is called on all modules, even ones that are disabled.
	 * Note that you cannot rely on other modules (such as languages) here, as they may not yet exist.
	 *
	 */
	public function __construct()
//    public function __construct(LinkedRecordService $linked_record_service)
	{
//        $this->linked_record_service = $linked_record_service;
	}


	/**
	 * list of const for module administration
	 */
	public const CUSTOM_TITLE       = 'MyList module';
	public const CUSTOM_MODULE      = 'WeEt';             // tbd change to "...."
	public const CUSTOM_AUTHOR      = 'Dr. Ansgar M. Häger';
	public const GITHUB_USER        = '08ah15';
	public const CUSTOM_WEBSITE     = 'https://github.com/' . self::GITHUB_USER . '/' . self::CUSTOM_MODULE . '/';
	public const CUSTOM_VERSION     = '0.0.3.9';
	public const CUSTOM_LAST        = 'https://raw.githubusercontent.com/' . self::GITHUB_USER . '/' .self::CUSTOM_MODULE . '/main/latest-version.txt';

	// Defaults



	/**
	 * Bootstrap.  This function is called on *enabled* modules.
	 * It is a good place to register routes and views.
	 * Note that it is only called on genealogy pages - not on admin pages.
	 *
	 * @return void
	 */
	public function boot(): void
	{
		Registry::routeFactory()->routeMap()
			->get(static::class, static::ROUTE_URL, $this)
			->allows(RequestMethodInterface::METHOD_POST);
	}

	/**
	 * How should this module be identified in the control panel, etc.?
	 *
	 * @return string
	 */
	public function title(): string
	{
		return I18N::translate(self::CUSTOM_TITLE);
	}

	/**
	 * A sentence describing what this module does.
	 *use function redirect;
	 * @return string
	 */
	public function description(): string
	{
		return I18N::translate('This module list sources in an archive you like to see during your next visit');
	}

	/**
	 * The person or organisation who created this module.
	 *
	 * @return string
	 */
	public function customModuleAuthorName(): string
	{
		return self::CUSTOM_AUTHOR;
	}

	/**
	 * The version of this module.
	 *
	 * @return string
	 */
	public function customModuleVersion(): string
	{
		return self::CUSTOM_VERSION;
	}

	/**
	 * A URL that will provide the latest version of this module.
	 *
	 * @return string
	 */
	public function customModuleLatestVersionUrl(): string
	{
		return self::CUSTOM_LAST;
	}

	/**
	 * Where to get support for this module.  Perhaps a github repository?
	 *
	 * @return string
	 */
	public function customModuleSupportUrl(): string
	{
		return self::CUSTOM_WEBSITE;
	}

	/**
	 * Additional/updated translations.
	 *
	 * @param string $language
	 *
	 * @return array<string>
	 */
	public function customTranslations(string $language): array
	{
		switch ($language) {
			case 'en-AU':
			case 'en-GB':
			case 'en-US':
				// Note the special characters used in plural and context-sensitive translations.
				return [
					'Individual'                                      => 'Fish',
					'Individuals'                                     => 'Fishes',
					'%s individual' . I18N::PLURAL . '%s individuals' => '%s fish' . I18N::PLURAL . '%s fishes',
					'Unknown given name' . I18N::CONTEXT . '…'        => '?fish?',
					'Unknown surname' . I18N::CONTEXT . '…'           => '?FISH?',
				];

			case 'fr':
			case 'fr-CA':
				return [
					// These are new translations:
					'Example module'                                  => 'Exemple module',
					'This module does not do anything'                => 'Ce module ne fait rien',
					// These are updates to existing translations:
					'Individual'                                      => 'Poisson',
					'Individuals'                                     => 'Poissons',
					'%s individual' . I18N::PLURAL . '%s individuals' => '%s poisson' . I18N::PLURAL . '%s poissons',
					'Unknown given name' . I18N::CONTEXT . '…'        => '?poission?',
					'Unknown surname' . I18N::CONTEXT . '…'           => '?POISSON?',
				];

			case 'de':
				return [
					'unkown'                                          => 'unbekannt',
					'Sources found about births:'                     => 'Quellen gefunden über Geburten:',
					', about marriages:'                              => ', über Heiraten:',
					', about deaths:'                                 => ', über Sterbefälle:',
					'Create file of format:'                          => 'Erzeuge Datei im Format:',
					'create file'                                     => 'Erzeuge Datei',
				];

			case 'some-other-language':
				// Arrays are preferred, and are faster.
				// If your module uses .MO files, then you can convert them to arrays like this.
				return (new Translation('path/to/file.mo'))->asArray();

			default:
				return [];
		}
	}

	/**
	 * Own actions
	 */

	/**
	 * Where does this module store its resources
	 *
	 * @return string
	 */
	public function resourcesFolder(): string
	{
		return __DIR__ . '/resources/';
	}


	/**
	 * The title for a specific instance of this list.
	 *
	 * @return string
	 */
	public function listTitle(): string {return 'MyList';}

	/**
	 * CSS class for the URL.
	 *
	 * @return string
	 */
	public function listMenuClass(): string
	{
		return 'menu-list-sour';
	}

	/**
	 * @param Tree                                      $tree
	 * @param array<bool|int|string|array<string>|null> $parameters
	 *
	 * @return string
	 */
	public function listUrl(Tree $tree, array $parameters = []): string
	{
		$parameters['tree'] = $tree->name();
		return route(static::class, $parameters);
	}

	/**
	 * @return array<string>
	 */
	public function listUrlAttributes(): array
	{
		return [];
	}

	/**
	 * @param Tree $tree
	 *
	 * @return bool
	 */
	public function listIsEmpty(Tree $tree): bool
	{
		return !DB::table('sources')
			->where('s_file', '=', $tree->id())
			->exists();
	}


	/**
	 * @param ServerRequestInterface $request
	 *
	 * @return ResponseInterface
	 */
	public function handle(ServerRequestInterface $request): ResponseInterface
	{
		$tree = Validator::attributes($request)->tree();
		$user = Validator::attributes($request)->user();

		Auth::checkComponentAccess($this, ModuleListInterface::class, $tree, $user);

		// Convert POST requests into GET requests for pretty URLs.
		if ($request->getMethod() === RequestMethodInterface::METHOD_POST) {
			$params = [
				'go'      => true,
				'repo'    => Validator::parsedBody($request)->string('repository'),
				'noObj'   => Validator::parsedBody($request)->boolean('noObj',true),
				'filter'  => Validator::parsedBody($request)->boolean('filter', true),
			];

			return redirect($this->listUrl($tree, $params));
		}

		$repos	= $this -> allArchives($tree);
		$go		= Validator::queryParams($request)->boolean('go', false);
		$repo	= Validator::queryParams($request)->string('repo', '');
		$noObj	= Validator::queryParams($request)->boolean('noObj', true);
		$filter	= Validator::queryParams($request)->boolean('filter', true);
		$rname	= '';

		if ($go) {
			$births 	= $this->SourcesInRepo($tree, $repo,'Geburtsurkunde%', $noObj);
			if($filter){$births = $this->sfilter($births,110);};
			$marriages	= $this->SourcesInRepo($tree, $repo,'Heiratsurkunde%', $noObj);
			if($filter){$marriages = $this->sfilter($marriages,80);};
			$deaths 	= $this->SourcesInRepo($tree, $repo,'Sterbeurkunde%', $noObj);
			if($filter){$deaths = $this->sfilter($deaths,30);};
			$count_b	= $births->count();
			$count_m	= $marriages->count();
			$count_d	= $deaths->count();
			$rname  	= $this->getArchive($tree, $repo);
			//$repo->fullname;
		}
		else {
			$births 	= new Collection();
			$marriages	= new Collection();
			$deaths 	= new Collection();
			$count_b	= 0;
			$count_m	= 0;
			$count_d	= 0;
		}


		return $this->viewResponse('../../modules_v4/WeEt/resources/views/page', [
			'archives'  		=> $repos,
			'count_b'   		=> $count_b,
			'count_m'   		=> $count_m,
			'count_d'   		=> $count_d,
			'repository'		=> $repo,
			'rname'     		=> $rname,
			'sources_b' 		=> $births,
			'sources_m' 		=> $marriages,
			'sources_d' 		=> $deaths,
			'noObj'     		=> $noObj,
			'filter'    		=> $filter,
			'title'     		=> I18N::translate('Sources'),
			'tree'      		=> $tree,
		]);

	}

	/**
	 * Generate the selected archive object.
	 *
	 * @param Tree $tree
	 * @param repo $repo     
	 *
	 * @return $archive
	 */
	public function getArchive(Tree $tree, string $repo): Repository
	{
		if($repo<>""){
			$query = DB::table('other')
				->where('o_file', '=', $tree->id())
				->where('o_type', '=', Repository::RECORD_TYPE)
				->where('o_id', '=', $repo)
				->get()
				->map(Registry::repositoryFactory()->mapper($tree));
			foreach ($query as $arch){$arch;}
		}
		else{$arch="";}
		return $arch;
	}

	/**
	 * Generate a list of all the archives in a current tree.
	 *
	 * @param Tree $tree
	 *
	 * @return array<string>
	 */
	private function allArchives(Tree $tree): Collection
	{
		$archives = DB::table('other')
			->where('o_file', '=', $tree->id())
			->where('o_type', '=', Repository::RECORD_TYPE)
			->where('o_gedcom', 'like', '%Standesamt%')
//			->orwhere('o_gedcom', 'like', '%Stadtarchiv%')
//			->orwhere('o_gedcom', 'like', '%Gemeindearchiv%')

			->get()
			->map(Registry::repositoryFactory()->mapper($tree))
//			->uniqueStrict()
			->filter(GedcomRecord::accessFilter())
			;
//		if ($archives->isEmpty){break;}

//		$arch = [];      

//		foreach ($archives as $archive) {
//			$archive->fullName();
//			array_push($archives,$archive->extractName());
//			$arch[] =   $archive->fullNames();
//			echo $archive->fullName();
//			$arch[] = $archive -> xref();
//		};    
//		$arch[]= 'Standesamt Remscheid';
//		$arch[]= array('R11','Standesamt Dabringhausen');
 
		// Ensure we have an empty (top level) folder.
//		array_unshift($arch, '');
//		Repository::extractNames()

		return $archives;

	}

	
	/**
	 * Filter Collection of sources.
	 *
	 * @param Collection $collect
	 * @param int        $grace
	 *
	 * @return Collection
	 */
	private function sfilter(Collection $collect,int $grace): Collection
	{
		$filtered_sources = new Collection();
		foreach($collect as $source){
			$year = mysource::getYear($source);
			if($year < date('Y')-$grace) {
				$filtered_sources[] = $source;
			}
		}
		$results = $filtered_sources;
		return $filtered_sources;
	}

	
	/**
	 * Generate a list of all sources matching the criteria in a current tree.
	 *
	 * @param Tree   $tree		find sources in this tree
	 * @param string $repo		repository to search
	 * @param string $cert		leading string in source title marking it as birth, marriage or death certificate
	 * @param bool   $sort		only show sources with or without media object
	 *
	 * @return Collection<int,Source>
	 */
	private function SourcesInRepo(Tree $tree, string $repo, string $cert, bool $without): Collection
	{
		$year	= date('Y');
		$query	= DB::table('sources')
					->where('s_file', '=', $tree->id())
					->where('s_name', 'LIKE' ,$cert);

	// Apply search terms
		if ($repo !== '') {
			$query	->where(static function (Builder $query) use ($repo): void {
				$query	->where('s_gedcom', 'LIKE', '%@'.$repo.'@%');
				});
		}
	// Apply filter ctriteria "object"
		if ($without) {
			$query	->where('s_gedcom', 'NOT LIKE', '%OBJE%');
		}
		$query	->orderByRaw("SUBSTRING_INDEX(s_name, '/', -1) DESC");
	
		return $query
			->get()
			->map(Registry::sourceFactory()->mapper($tree))
			->uniqueStrict()
			->filter(GedcomRecord::accessFilter());
	}

}

class mysource extends source
{
	/**
	 * Get year of sources publication.
	 *
	 * @param source $source
	 *
	 * @return int
	 */
	static function getYear(GedcomRecord $source):int
	{
		$year = (int)substr($source->fullName(), (strpos($source->fullName(),'/')+1),4);
		return $year;
	}

};

