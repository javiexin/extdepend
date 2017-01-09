# Extension Dependency Management

## Testing

A set of extensions that do nothing is provided with this extension, so that it might be tested.
There are cases of direct and indirect dependencies, different ordering of dependencies,
circular dependencies at two and three levels, and extensions that do not participate in
a circular dependecy but depend on an extension that does.

All these extensions should be copied to ext/extdeptest/*.  You may copy the content of the
"testing" folder directly to the root of your test forum.

Of course, you are free to (and probably should) make any and all changes that you see fit,
to test different situations.

Also, try enabling some of these extensions before enabling the Extension Manager, to see it 
working in more situations.
