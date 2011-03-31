import sys, httplib, urllib, hashlib, hmac, base64
import json


def canonicalize(method, host, uri, params):
    """
    Return a string of canonically joined request attributes, suitable for
    signature comparison.
    """
    canon = [method.upper(), host.lower(), uri]

    args = []
    for key in sorted(params.keys()):
        val = ','.join(sorted(params[key]))
        args.append(
            '%s=%s' % (urllib.quote(key, '~'), urllib.quote(val, '~')))
    canon.append('&'.join(args))

    return '\n'.join(canon)

def sign(ikey, skey, method, host, uri, params):
    """
    Return basic authorization header line with a Duo Web API signature.
    """
    sig = hmac.new(skey, canonicalize(method, host, uri, params), hashlib.sha1)
    auth = '%s:%s' % (ikey, sig.hexdigest())
    return 'Basic %s' % base64.b64encode(auth)

def call(ikey, skey, host, method, path, **kwargs):
    """
    Call a Duo Web API method and return a (status, reason, data) tuple.
    """
    headers = {'Authorization':sign(ikey, skey, method, host, path, kwargs)}

    if method in [ 'POST', 'PUT' ]:
        headers['Content-type'] = 'application/x-www-form-urlencoded'
        body = urllib.urlencode(kwargs, doseq=True)
        uri = path
    else:
        body = None
        uri = path + '?' + urllib.urlencode(kwargs, doseq=True)

    conn = httplib.HTTPSConnection(host, 443)
    conn.request(method, uri, body, headers)
    response = conn.getresponse()
    data = response.read()
    conn.close()
    
    return (response.status, response.reason, data)

def call_json_api(ikey, skey, host, method, path, **kwargs):
    """
    Call a Duo Web API method which is expected to return a standard JSON
    body with a 200 status.  Return the response element, or raise
    RuntimeError.
    """
    (status, reason, data) = call(ikey, skey, host, method, path, **kwargs)
    if status != 200:
        raise RuntimeError('Received %s %s: %s' % (status, reason, data))
    try:
        data = json.loads(data)
        if data['stat'] != 'OK':
            raise RuntimeError('Received error response: %s' % data)
        return data['response']
    except (ValueError, KeyError):
        raise RuntimeError('Received bad responss: %s' % data)
    
