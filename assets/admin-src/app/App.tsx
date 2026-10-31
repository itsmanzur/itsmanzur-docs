import { Button, Notice, Spinner } from '@wordpress/components';
import { useDispatch, useSelect } from '@wordpress/data';
import { lazy, Suspense, useCallback, useEffect, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { api } from '../api';
import { store } from '../store';
import { DocsManager } from '../components/DocsManager';
import { Glossary } from '../components/Glossary';
import { HelpGuide } from '../components/HelpGuide';
import { ImportExport } from '../components/ImportExport';
import { Sections } from '../components/Sections';
import { Settings } from '../components/Settings';
import { SetupWizard } from '../components/SetupWizard';
import { getOnboardingStatus, completeOnboardingStep } from '../components/onboarding/onboardingApi';

const OnboardingWizard = lazy(
	() =>
		import(
			/* webpackChunkName: "onboarding" */
			'../components/onboarding/OnboardingWizard'
		)
);

type View =
	| 'docs'
	| 'sections'
	| 'glossary'
	| 'settings'
	| 'transfer'
	| 'guide'
	| 'wizard'
	| 'onboarding';

const viewFromHash = (): View => {
	const hash = window.location.hash.replace( '#/', '' );
	return [
		'docs',
		'sections',
		'glossary',
		'settings',
		'transfer',
		'guide',
		'wizard',
		'onboarding',
	].includes( hash )
		? ( hash as View )
		: 'docs';
};

export function App() {
	const [ view, setView ] = useState< View >( viewFromHash );
	const [ onboardingOpen, setOnboardingOpen ] = useState( false );
	const { projects, articles, loading, notice, selectedProjectId } =
		useSelect(
			( select ) => ( {
				projects: select( store ).getProjects(),
				articles: select( store ).getArticles(),
				loading: select( store ).isLoading(),
				notice: select( store ).getNotice() as {
					status: 'success' | 'error';
					message: string;
				} | null,
				selectedProjectId: select( store ).getSelectedProjectId(),
			} ),
			[]
		);
	const {
		setProjects,
		setArticles,
		setSections,
		selectProject,
		setLoading,
		setNotice,
	} = useDispatch( store );

	const loadWorkspace = useCallback( async () => {
		setLoading( true );
		try {
			const [ loadedProjects, onboarding ] = await Promise.all( [
				api.listProjects(),
				getOnboardingStatus().catch( () => null ),
			] );
			setProjects( loadedProjects );

			if ( onboarding?.should_show || 'onboarding' === viewFromHash() ) {
				setOnboardingOpen( true );
			}

			if ( loadedProjects.length ) {
				const projectId = selectedProjectId ?? loadedProjects[ 0 ].id;
				selectProject( projectId );
				const [ loadedArticles, sections ] = await Promise.all( [
					api.listArticles( projectId ),
					api.listSections(),
				] );
				setArticles( loadedArticles );
				setSections( sections );
			}
		} catch ( error ) {
			setNotice( {
				status: 'error',
				message:
					error instanceof Error
						? error.message
						: __(
								'Nirdeshio could not load the workspace.',
								'itsmanzur-docs'
						  ),
			} );
		} finally {
			setLoading( false );
		}
	}, [
		selectProject,
		selectedProjectId,
		setArticles,
		setLoading,
		setNotice,
		setProjects,
		setSections,
	] );

	useEffect( () => {
		void loadWorkspace();
	}, [ loadWorkspace ] );

	useEffect( () => {
		const handleHashChange = () => {
			const next = viewFromHash();
			setView( next );
			if ( 'onboarding' === next ) {
				setOnboardingOpen( true );
			}
		};
		window.addEventListener( 'hashchange', handleHashChange );
		return () =>
			window.removeEventListener( 'hashchange', handleHashChange );
	}, [] );

	const navigate = ( nextView: View ) => {
		window.location.hash = `/${ nextView }`;
		setView( nextView );
	};

	const closeOnboarding = async () => {
		setOnboardingOpen( false );
		await loadWorkspace();
		navigate( 'docs' );
	};

	if ( loading ) {
		return (
			<div className="itsdz-loading" role="status">
				<Spinner />
				<span>{ __( 'Loading Nirdeshio…', 'itsmanzur-docs' ) }</span>
			</div>
		);
	}

	if ( onboardingOpen || view === 'onboarding' ) {
		return (
			<Suspense
				fallback={
					<div className="itsdz-loading" role="status">
						<Spinner />
						<span>{ __( 'Loading setup…', 'itsmanzur-docs' ) }</span>
					</div>
				}
			>
				<OnboardingWizard
					onComplete={ closeOnboarding }
					onSkip={ closeOnboarding }
				/>
			</Suspense>
		);
	}

	if ( view === 'wizard' ) {
		return (
			<SetupWizard
				onComplete={ async () => {
					await loadWorkspace();
					navigate( 'docs' );
				} }
				onCancel={
					projects.length ? () => navigate( 'docs' ) : undefined
				}
			/>
		);
	}

	if ( ! projects.length ) {
		return (
			<div className="itsdz-wizard">
				<div className="itsdz-wizard-heading">
					<h1>{ __( 'Nirdeshio', 'itsmanzur-docs' ) }</h1>
					<p>
						{ __(
							'Create a documentation project when you are ready. Setup is optional and can be skipped.',
							'itsmanzur-docs'
						) }
					</p>
				</div>
				<div className="itsdz-onboarding-actions">
					<Button
						variant="primary"
						onClick={ () => {
							void completeOnboardingStep( { restart: true } ).then(
								() => {
									setOnboardingOpen( true );
									navigate( 'onboarding' );
								}
							);
						} }
					>
						{ __( 'Open setup wizard', 'itsmanzur-docs' ) }
					</Button>
					<Button variant="secondary" onClick={ () => navigate( 'wizard' ) }>
						{ __( 'Create a project', 'itsmanzur-docs' ) }
					</Button>
				</div>
			</div>
		);
	}

	const selectedProject =
		projects.find( ( project ) => project.id === selectedProjectId ) ??
		projects[ 0 ];

	return (
		<div className="itsdz-app-shell">
			<header className="itsdz-topbar">
				<div>
					<span className="itsdz-brand-mark" aria-hidden="true">
						N
					</span>
					<strong>Nirdeshio</strong>
					<span className="itsdz-version">
						v{ window.itsdzAdmin.pluginVersion }
					</span>
				</div>
			</header>

			<div className="itsdz-workspace">
				<nav
					className="itsdz-app-nav"
					aria-label={ __( 'Nirdeshio navigation', 'itsmanzur-docs' ) }
				>
					{ (
						[
							[
								'docs',
								__( 'Documentation', 'itsmanzur-docs' ),
								'dashicons-media-document',
							],
							[
								'sections',
								__( 'Sections', 'itsmanzur-docs' ),
								'dashicons-category',
							],
							[
								'glossary',
								__( 'Glossary', 'itsmanzur-docs' ),
								'dashicons-book-alt',
							],
							[
								'settings',
								__( 'Settings', 'itsmanzur-docs' ),
								'dashicons-admin-settings',
							],
							[
								'transfer',
								__( 'Import / Export', 'itsmanzur-docs' ),
								'dashicons-database-export',
							],
							[
								'guide',
								__( 'Help & Guide', 'itsmanzur-docs' ),
								'dashicons-editor-help',
							],
						] as [ View, string, string ][]
					 ).map( ( [ itemView, label, iconClass ] ) => (
						<Button
							key={ itemView }
							className={ view === itemView ? 'is-active' : '' }
							onClick={ () => navigate( itemView ) }
							aria-current={
								view === itemView ? 'page' : undefined
							}
						>
							<span
								className={ `dashicons ${ iconClass }` }
								aria-hidden="true"
								style={ {
									marginInlineEnd: '8px',
									fontSize: '17px',
									width: '17px',
									height: '17px',
								} }
							/>
							{ label }
						</Button>
					) ) }
				</nav>

				<main className="itsdz-main">
					{ notice && (
						<Notice
							status={ notice.status }
							onRemove={ () => setNotice( null ) }
						>
							{ notice.message }
						</Notice>
					) }
					{ view === 'docs' && (
						<DocsManager project={ selectedProject } />
					) }
					{ view === 'sections' && <Sections /> }
					{ view === 'glossary' && <Glossary /> }
					{ view === 'settings' && (
						<Settings
							project={ selectedProject }
							onRerunWizard={ async () => {
								await completeOnboardingStep( { restart: true } );
								setOnboardingOpen( true );
								navigate( 'onboarding' );
							} }
						/>
					) }
					{ view === 'transfer' && (
						<ImportExport project={ selectedProject } />
					) }
					{ view === 'guide' && (
						<HelpGuide
							project={ selectedProject }
							articles={ articles }
							onNavigate={ navigate }
						/>
					) }
				</main>
			</div>
		</div>
	);
}
