# Extension Dependency Management

## Installation

Copy the extension to phpBB/ext/javiexin/extdependencies

Go to "ACP" > "Customise" > "Extensions" and enable the "Extension Dependency Management" extension.

## How does it work?

This extension does not provide any function on its own, except making sure that the declared 
Extension Depencies are maintained throughout the lifecycle of extensions.

### Extension checks

When an extension is enabled, all its dependencies are validated.  If any of them is not available,
then the enable process will trigger an error, and specify what extension(s) is(are) missing.

If the extension dependencies are available, but not enabled, the user will be asked for permission
to enable ALL dependencies, along with the extension being enabled.  If any of them fail to enable,
the process will be interrupted, but NOT reversed: the changes that have already occurred will stay
(some extensions might have been enabled, before others failed to enable).  The enable order makes
sure that the state will be consistent after each step (base extensions will be enabled before any
depending extension).

Similarly, on disable, an extension will behave exactly the same as now if it has no extensions that
depend on it.  But, if any enabled extension has declared the dependency to this one, then the user
will be asked for confirmation that it is ok to disable ALL depending extensions together with the
base extension.  Again, if anything fails, the changes are NOT transactional, so some things may be
partially done (some extensions disabled, others no).  The disable order is reversed, so no extension
that has dependencies will be disabled before any of its dependencies is still enabled.

In the case of circular dependencies (A depends on B, that depends on A, or more complex ones),
the Dependency Manager detects this situation and signals it in the extension list, plus the 
extensions involved are NOT available for enable (the link to enable them is removed).  If, for
whatever reason, the enable is attempted anyways, it will fail as well.

Now, in case you have disabled the Extension Dependency Manager, all of these checks are not
performed, and your board may have issues.  When you enable the Dependency Manager for the first
time, the installed extensions will be checked, and adecuate actions will be performed, or signaled
as required (nothing is done without explicit user approval).

If an extension that has dependencies on others was enabled before the Dependency Manager, but
the dependencies are not met, the extension is signaled as faulty, and a new "Revalidate" process
may be initiated (new action on the extension).  This process first disables the extension, and then 
reenables it, checking for the dependencies.

If an extension that participates in a circular dependency is enabled when the Dependency Manager
is enabled, this is clearly flagged, and the only available option is the disabling of the extension;
however, this is not done automatically, as it has to be "approved" by the user.

### Dependency information

All the dependencies are shown in the Extension Details page.  A list of required extensions, and
a list of depending extensions, are both included (if there are any such dependencies).

### Instructions for extension authors for subordinate extensions

To "declare" a dependency, the subordinate extension must define a method in the ext.php file,
much like what it is done for migration dependencies:

	public function depends_on()
	{
		return array(
			'vendor1/extension1'	=> '>=1.0.0@dev', // version string with required version to compare to
			'vendor2/extension2'	=> '>=1.0.0@dev', // as many entries as needed, in whatever order
		);
	}

This should ONLY be used if this is a HARD dependency: if the dependency is not present, the depending
extension will stop to work, or create significant forum problems and malfunctions.  It is always preferable
to have SOFT dependencies, where the subordinate extension would fail over to a less-optimal behaviour,
or even, stop working without causing other problems.

If you really want to make it safe, then you should also declare, in ext.php:

	public function is_enableable()
	{
		return $this->container->get('ext.manager')->is_enabled('javiexin/extdependencies');
	}

This will make sure that your extension will not be enabled unless the Dependency Manager is at work, in
which case the lifecycle of dependencies will be automaticall maintained.  Note that you do NOT need to
explicitly add a dependency on the Extension Dependency Manager itself, as if you declare a "depends_on"
function, this dependency will be included automatically.

### Instructions for extension authors for base extensions

Nothing to do, no need to include anything at all.  All the management will be automated.

### TO DO

At the moment, the version information is NOT used at all.  This will go into a next version.

## License

[GPLv3](license.txt)
