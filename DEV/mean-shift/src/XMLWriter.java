/*
 * Copyright (c) 2006 Sun Microsystems, Inc.  All rights reserved.  U.S.
 * Government Rights - Commercial software.  Government users are subject
 * to the Sun Microsystems, Inc. standard license agreement and
 * applicable provisions of the FAR and its supplements.  Use is subject
 * to license terms.
 *
 * This distribution may include materials developed by third parties.
 * Sun, Sun Microsystems, the Sun logo, Java and J2EE are trademarks
 * or registered trademarks of Sun Microsystems, Inc. in the U.S. and
 * other countries.
 *
 * Copyright (c) 2006 Sun Microsystems, Inc. Tous droits reserves.
 *
 * Droits du gouvernement americain, utilisateurs gouvernementaux - logiciel
 * commercial. Les utilisateurs gouvernementaux sont soumis au contrat de
 * licence standard de Sun Microsystems, Inc., ainsi qu'aux dispositions
 * en vigueur de la FAR (Federal Acquisition Regulations) et des
 * supplements a celles-ci.  Distribue par des licences qui en
 * restreignent l'utilisation.
 *
 * Cette distribution peut comprendre des composants developpes par des
 * tierces parties. Sun, Sun Microsystems, le logo Sun, Java et J2EE
 * sont des marques de fabrique ou des marques deposees de Sun
 * Microsystems, Inc. aux Etats-Unis et dans d'autres pays.
 */

import java.io.IOException;
import java.io.Writer;
import java.util.ArrayList;

import org.xml.sax.Attributes;
import org.xml.sax.SAXException;
import org.xml.sax.helpers.AttributesImpl;
import org.xml.sax.helpers.DefaultHandler;

public class XMLWriter extends DefaultHandler {
	int precision = 100;
	public Writer out;
	StringBuffer textBuffer;
	public ArrayList<Cluster> EventClusters;
	public ArrayList<Cluster> SequenceClusters;
	public ArrayList<Cluster> ChunkCluster;

	protected boolean m_inCluster = true;

	// ===========================================================
	// SAX DocumentHandler methods
	// ===========================================================
	public void startDocument() throws SAXException {
		emit("<?xml version='1.0' encoding='UTF-8'?>");
		nl();
	}

	public void endDocument() throws SAXException {
		try {
			nl();
			out.flush();
		} catch (IOException e) {
			throw new SAXException("I/O error", e);
		}
	}

	public void startElement(String namespaceURI, String sName, // simple name
			String qName, // qualified name
			Attributes attrs) throws SAXException {
		echoText();

		String eName = sName; // element name

		if ("".equals(eName)) {
			eName = qName; // not namespaceAware
		}

		emit("<" + eName);

		if (attrs != null) {
			for (int i = 0; i < attrs.getLength(); i++) {
				String aName = attrs.getLocalName(i); // Attr name

				if ("".equals(aName)) {
					aName = attrs.getQName(i);
				}

				emit(" ");
				emit(aName + "=\"" + attrs.getValue(i) + "\"");
			}
		}

		emit(">");
		if ((eName.equals("sn:Substitutions")) && (m_inCluster)) {
			AttributesImpl attr = new AttributesImpl();
			for (int i = 0; i < SequenceClusters.size(); i++) {
				attr.clear();
				attr.addAttribute("", "id", "", "", "snappi-substitute-"
						+ Integer.toString(i));
				attr.addAttribute("", "Type", "", "", "Sequence");
				attr.addAttribute("", "score", "", "", Double.toString(Math
						.floor(SequenceClusters.get(i).Score * precision + .5)
						/ precision));
				startElement(namespaceURI, "", "sn:Substitution", attr);
				iterateOverVectors(SequenceClusters.get(i), attr);
				endElement(namespaceURI, "", "sn:Substitution");
				nl();
			}
		} else if ((eName.equals("sn:Clusters")) && (m_inCluster)) {
			AttributesImpl attr = new AttributesImpl();
			for (int i = 0; i < EventClusters.size(); i++) {
				attr.clear();
				attr.addAttribute("", "id", "", "", "snappi-cluster-"
						+ Integer.toString(i));
				attr.addAttribute("", "Type", "", "", "Event");
				attr.addAttribute("", "score", "", "", Double.toString(Math
						.floor(EventClusters.get(i).Score * precision + .5)
						/ precision));
				startElement(namespaceURI, "", "sn:Cluster", attr);
				iterateOverVectors(EventClusters.get(i), attr);
				endElement(namespaceURI, "", "sn:Cluster");
				nl();
			}

			for (int i = 0; i < ChunkCluster.size(); i++) {
				attr.clear();
				attr.addAttribute("", "id", "", "", "snappi-cluster-chunk-"
						+ Integer.toString(i));
				attr.addAttribute("", "Type", "", "", "Chunk");
				startElement(namespaceURI, "", "sn:Cluster", attr);
				iterateOverVectors(ChunkCluster.get(i), attr);
				endElement(namespaceURI, "", "sn:Cluster");
				nl();
			}

		} else if (eName.equals("sn:Auditions"))
			m_inCluster = false;
	}

	protected void iterateOverVectors(Cluster cls, AttributesImpl attr) {
		for (int i = 0; i < cls.Items.size(); i++) {
			try {
				attr.clear();
				attr.addAttribute("", "idref", "", "", (String) cls.Items
						.get(i).RawVector[1]);
				attr.addAttribute("", "score", "", "", Double.toString(Math
						.floor(cls.Items.get(i).Score * precision + .5)
						/ precision));
				startElement("", "", "sn:AuditionREF", attr);
				endElement("", "", "sn:AuditionREF");
			} catch (Exception e) {
				e.printStackTrace();
			}
		}
	}

	public void endElement(String namespaceURI, String sName, // simple name
			String qName // qualified name
	) throws SAXException {
		echoText();

		String eName = sName; // element name

		if ("".equals(eName)) {
			eName = qName; // not namespaceAware
		}

		emit("</" + eName + ">");
		if (eName.equals("sn:Auditions"))
			m_inCluster = true;
	}

	public void characters(char[] buf, int offset, int len) throws SAXException {
		String s = new String(buf, offset, len);

		if (textBuffer == null) {
			textBuffer = new StringBuffer(s);
		} else {
			textBuffer.append(s);
		}
	}

	// ===========================================================
	// Utility Methods ...
	// ===========================================================
	private void echoText() throws SAXException {
		if (textBuffer == null) {
			return;
		}

		String s = "" + textBuffer;
		emit(s);
		textBuffer = null;
	}

	// Wrap I/O exceptions in SAX exceptions, to
	// suit handler signature requirements
	private void emit(String s) throws SAXException {
		try {
			out.write(s);
			// out.flush();
		} catch (IOException e) {
			throw new SAXException("I/O error", e);
		}
	}

	// Start a new line
	private void nl() throws SAXException {
		String lineEnd = System.getProperty("line.separator");

		try {
			out.write(lineEnd);
		} catch (IOException e) {
			throw new SAXException("I/O error", e);
		}
	}
}
