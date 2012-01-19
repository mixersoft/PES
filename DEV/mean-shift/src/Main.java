import java.io.File;
import java.io.FileWriter;
import java.util.ArrayList;
import java.util.Date;

import javax.xml.parsers.SAXParser;
import javax.xml.parsers.SAXParserFactory;

public class Main {
	public static double EPS = 0.0001;
	public static double EPS_CLUSTER = 0.001;

	public static double dist(double[] x, double[] y) {
		double res = 0.0;
		for (int i = 0; i < x.length; i++)
			res = res + Math.pow(x[i] - y[i], 2);
		return Math.sqrt(res);
	}

	public static double norm(double[] x) {
		double res = 0.0;
		for (int i = 0; i < x.length; i++)
			res = res + Math.pow(x[i], 2);
		return Math.sqrt(res);
	}

	public static double gauss_kernel(double[] x, double[] x_i, double h) {
		return Math.exp(-Math.pow(norm(VectorMath.sub(x_i, x)) / h, 2));
	}

	public static void merge_lists(ArrayList<Cluster> classes,
			ArrayList<Cluster> cls) {
		for (int i = 0; i < cls.size(); i++)
			classes.add(cls.get(i));
	}

	public static ArrayList<Cluster> mean_shift_group(ArrayList<double[]> vecs,
			ArrayList<Object[]> raw, double h, double div_val) {
		ArrayList<Cluster> classes = new ArrayList<Cluster>();
		ArrayList<double[]> new_vecs = new ArrayList<double[]>();
		ArrayList<Object[]> new_raw = new ArrayList<Object[]>();

		for (int i = 1; i < vecs.size(); i++) {
			if (vecs.get(i)[0] - vecs.get(i - 1)[0] > div_val) {
				ArrayList<Cluster> cls = mean_shift(new_vecs, new_raw, h);
				merge_lists(classes, cls);
				new_vecs = new ArrayList<double[]>();
				new_raw = new ArrayList<Object[]>();
			}
			new_vecs.add(vecs.get(i));
			new_raw.add(raw.get(i));
		}

		ArrayList<Cluster> cls = mean_shift(new_vecs, new_raw, h);
		merge_lists(classes, cls);

		return classes;
	}

	public static void make_scores(ArrayList<Cluster> classes) {
		ArrayList<double[]> cntrs = new ArrayList<double[]>();

		for (int i = 0; i < classes.size(); i++)
			cntrs.add(classes.get(i).Centre);

		for (int i = 0; i < classes.size(); i++) {
			Cluster cl = classes.get(i);
			cl.clusterScore();

			double max_dist = 0.0;
			double max_mean_dist = 0.0;
			double[] mean = VectorMath.zero(cl.Centre);

			for (int j = 0; j < cl.Items.size(); j++)
				mean = VectorMath.add(mean, cl.Items.get(j).Vector);

			mean = VectorMath.mul(mean, 1.0 / (double) cl.Items.size());

			for (int j = 0; j < cl.Items.size(); j++) {
				double d = dist(cl.Centre, cl.Items.get(j).Vector);
				if (d > max_dist)
					max_dist = d;
				d = dist(mean, cl.Items.get(j).Vector);
				if (d > max_mean_dist)
					max_mean_dist = d;
			}

			for (int j = 0; j < cl.Items.size(); j++) {
				Item it = cl.Items.get(j);
				double score = 1.0;
				if (max_dist != 0.0)
					score = score - 0.25 * dist(it.Vector, cl.Centre)
							/ max_dist;
				if (max_mean_dist != 0.0)
					score = score - 0.25 * dist(it.Vector, mean)
							/ max_mean_dist;

				int closest = 0;
				double closest_dist = 100000000000.0;

				for (int k = 0; k < cntrs.size(); k++) {
					double d = dist(cntrs.get(k), it.Vector);
					if ((k != i) && (d < closest_dist)) {
						closest_dist = d;
						closest = k;
					}

				}

				double closest_prop = 1.0;
				if (dist(it.Vector, cntrs.get(closest)) != 0.0)
					closest_prop = dist(it.Vector, cl.Centre)
							/ dist(it.Vector, cntrs.get(closest));

				if (closest_prop > 1.0)
					closest_prop = 1.0;

				score = score - closest_prop * 0.3;

				if (score < 0.1)
					score = 0.1;

				it.Score = score;
			}
		}
	}

	public static ArrayList<Cluster> mean_shift(ArrayList<double[]> vecs,
			ArrayList<Object[]> raw, double h) {
		ArrayList<Cluster> classes = new ArrayList<Cluster>();
		for (int i = 0; i < vecs.size(); i++) {
			double[] x = vecs.get(i).clone();
			// System.out.println("****************");
			// System.out.println(i);
			while (true) {
				double[] up = VectorMath.zero(x);
				double down = 0.0;

				for (int j = 0; j < vecs.size(); j++) {
					double[] x_i = vecs.get(j);
					double kernel = gauss_kernel(x, x_i, h);
					up = VectorMath.add(up, VectorMath.mul(x_i, kernel));
					down = down + kernel;
				}
				double[] x_new = VectorMath.mul(up, 1 / down);

				if (dist(x, x_new) < EPS)
					break;
				x = VectorMath.mul(up, 1 / down);
			}

			boolean bb = false;
			for (int cl_pos = 0; cl_pos < classes.size(); cl_pos++) {
				Cluster c = classes.get(cl_pos);
				if (dist(c.Centre, x) < EPS_CLUSTER) {
					c.Count++;
					c.Items.add(new Item(vecs.get(i), raw.get(i)));
					bb = true;
					break;
				}
			}
			if (!bb)
				classes
						.add(new Cluster(x, 1,
								new Item(vecs.get(i), raw.get(i))));
		}
		return classes;
	}

	public static double calc_config_cost(Cluster cluster,
			ArrayList<double[]> medoids) {
		ArrayList<Item> items = cluster.Items;
		double cost = 0.0;
		for (int i = 0; i < items.size(); i++) {
			double min_dist = 10000000000.0;
			for (int j = 0; j < medoids.size(); j++) {
				double d = dist(medoids.get(j), items.get(i).Vector);
				if (min_dist > d)
					min_dist = d;
			}
			cost += min_dist;
		}
		return cost;
	}

	public static int find_cluster_k(Cluster cluster, double good_photo) {
		ArrayList<Item> items = cluster.Items;
		int k_number = 0;

		for (int i = 0; i < items.size(); i++) {
			Item it = items.get(i);
			if ((Double) it.RawVector[3] >= good_photo) {
				k_number++;
			}
		}

		return k_number;
	}

	public static ArrayList<Cluster> assign_to_cluster_k(Cluster cluster,
			ArrayList<double[]> means, int k_number, double good_photo) {
		ArrayList<Item> items = cluster.Items;
		ArrayList<Cluster> chunks = new ArrayList<Cluster>();

		for (int i = 0; i < k_number; i++) {
			chunks.add(new Cluster());
		}

		for (int i = 0; i < items.size(); i++) {
			double min_dist = 10000000000.0;
			int closest = 0;
			for (int j = 0; j < means.size(); j++) {
				double d = dist(means.get(j), items.get(i).Vector);
				if (min_dist > d) {
					min_dist = d;
					closest = j;
				}
			}

			Item new_it = items.get(i).copy();
			new_it.Score = min_dist / (Double) items.get(i).RawVector[3];
			chunks.get(closest).Items.add(new_it);
			chunks.get(closest).Count++;
		}

		// filter clusters that doesn't contain any of good photos
		ArrayList<Cluster> filtered_chunks = new ArrayList<Cluster>();

		for (int i = 0; i < k_number; i++) {
			boolean bb = false;
			Cluster cl = chunks.get(i);
			for (int j = 0; j < cl.Items.size(); j++) {
				bb = bb || (Double) cl.Items.get(j).RawVector[3] >= good_photo;
			}
			if (bb)
				filtered_chunks.add(cl);
		}
		return filtered_chunks;
	}

	public static ArrayList<Cluster> weight_kmedoids(Cluster cluster,
			double good_photo, int max_iter) {
		int k_number = find_cluster_k(cluster, good_photo);
		if (k_number == 0)
			return new ArrayList<Cluster>();

		ArrayList<Item> items = cluster.Items;

		ArrayList<double[]> medoids = new ArrayList<double[]>();

		for (int i = 0; i < k_number; i++) {
			medoids.add(items.get(i).Vector);
		}

		double current_cost = 100000000.0;

		for (int t = 0; t < max_iter; t++) {
			double min_cost = 1000000000.0;
			ArrayList<double[]> best_medoids = new ArrayList<double[]>();
			for (int i = 0; i < k_number; i++) {
				for (int j = 0; j < items.size(); j++) {
					ArrayList<double[]> new_medoids = (ArrayList) medoids.clone();
					new_medoids.set(i, items.get(j).Vector);
					double cost = calc_config_cost(cluster, new_medoids)
							/ (Double) items.get(j).RawVector[3];
					if (min_cost > cost) {
						best_medoids = (ArrayList) new_medoids.clone();
						min_cost = cost;
					}
				}
			}

			if (min_cost >= current_cost)
				break;

			medoids = best_medoids;
			current_cost = min_cost;
		}

		return assign_to_cluster_k(cluster, medoids, k_number, good_photo);
	}

	public static ArrayList<Cluster> weight_kmean(Cluster cluster,
			double good_photo, int max_iter) {
		ArrayList<Item> items = cluster.Items;
		int k_number = find_cluster_k(cluster, good_photo);

		if (k_number == 0)
			return new ArrayList<Cluster>();

		ArrayList<double[]> means = new ArrayList<double[]>();

		for (int i = 0; i < k_number; i++) {
			means.add(items.get(i).Vector);
		}

		// weighted k-means
		for (int t = 0; t < max_iter; t++) {
			ArrayList<double[]> new_means_sum = new ArrayList<double[]>();
			ArrayList<Double> new_wgt_sum = new ArrayList<Double>();
			// fill zeros
			for (int i = 0; i < k_number; i++) {
				new_means_sum.add(VectorMath.zero(means.get(i)));
				new_wgt_sum.add(0.0);
			}

			for (int i = 0; i < items.size(); i++) {
				Item it = items.get(i);
				double min_dist = 10000000000.0;
				int closest = 0;
				for (int j = 0; j < means.size(); j++) {
					double d = dist(means.get(j), it.Vector);
					if (min_dist > d) {
						min_dist = d;
						closest = j;
					}
				}
				new_wgt_sum.set(closest, new_wgt_sum.get(closest)
						+ (Double) it.RawVector[3]);
				double[] tmp = VectorMath.mul(it.Vector,
						(Double) it.RawVector[3]);
				new_means_sum.set(closest, VectorMath.add(new_means_sum
						.get(closest), tmp));
			}

			means.clear();
			for (int i = 0; i < k_number; i++) {
				means.add(VectorMath.mul(new_means_sum.get(i),
						1.0 / new_wgt_sum.get(i)));
			}
		}

		// assign item to clusters by distance between centre of cluster and
		// item's Vector

		return assign_to_cluster_k(cluster, means, k_number, good_photo);
	}

	public static void main(String[] args) {
		Settings stng = new Settings(args);
		XMLWorker2 xml = new XMLWorker2();
		SAXParserFactory factory = SAXParserFactory.newInstance();
		SAXParser saxParser = null;
		try {
			saxParser = factory.newSAXParser();
			saxParser.parse(new File(stng.getParamStr("input")), xml);
		} catch (Exception e) {
			e.printStackTrace();
		}

		ArrayList<Cluster> clusters_seq = new ArrayList<Cluster>();
		ArrayList<Cluster> clusters_evs = new ArrayList<Cluster>();
		ArrayList<Cluster> clusters_chunks = new ArrayList<Cluster>();

		System.out.println(stng.getParam("bandwidth"));
		// xml.parse("ex.xml");
		Date dd = new Date();
		long start = dd.getTime();

		if (stng.getParam("doClustering") == 1.0) {
			if (stng.getParam("divideEventValue") > 0) {
				clusters_evs = mean_shift_group(xml.Vectors, xml.RawData, stng
						.getParam("bandwidth"), stng
						.getParam("divideEventValue"));
			} else
				clusters_evs = mean_shift(xml.Vectors, xml.RawData, stng
						.getParam("bandwidth"));
			make_scores(clusters_evs);
			for (int i = 0; i < clusters_evs.size(); i++) {
				clusters_evs.get(i).filterCluster(
						stng.getParam("filterEventValue"));
				if (stng.getParam("doChunkCl") == 1.0) {
					if (stng.getParam("chunkAlgo") == 1.0) {
						merge_lists(clusters_chunks, weight_kmean(clusters_evs
								.get(i), Math.pow(3, stng
								.getParam("goodPhotoRating")), 10));
					} else if (stng.getParam("chunkAlgo") == 2.0) {
						merge_lists(clusters_chunks, weight_kmedoids(
								clusters_evs.get(i), Math.pow(3, stng
										.getParam("goodPhotoRating")), 10));
					}

				}
			}
			System.out.println("Number of event clusters: "
					+ clusters_evs.size());
			System.out.println("Number of chunks clusters: "
					+ clusters_chunks.size());

		}

		if (stng.getParam("doSequenceCl") == 1.0) {
			if (stng.getParam("divideSequenceValue") > 0) {
				clusters_seq = mean_shift_group(xml.Vectors, xml.RawData, stng
						.getParam("bandwidthForSequence"), stng
						.getParam("divideSequenceValue"));
			} else
				clusters_seq = mean_shift(xml.Vectors, xml.RawData, stng
						.getParam("bandwidthForSequence"));

			ArrayList<Cluster> new_clus = new ArrayList<Cluster>();
			for (int i = 0; i < clusters_seq.size(); i++)
				if (clusters_seq.get(i).Items.size() > 1)
					new_clus.add(clusters_seq.get(i));
			clusters_seq = new_clus;
			make_scores(clusters_seq);
			System.out.println("Number of sequence clusters: "
					+ clusters_seq.size());
		}
		System.out.println("Algorithm running time - "
				+ ((new Date()).getTime() - start) + " ms.");

		try {
			XMLWriter xml_w = new XMLWriter();
			xml_w.out = new FileWriter(new File(stng.getParamStr("output")));
			xml_w.EventClusters = clusters_evs;
			xml_w.SequenceClusters = clusters_seq;
			xml_w.ChunkCluster = clusters_chunks;

//			if (stng.getParam("append") == 1.0) {
//				saxParser.parse(new File(stng.getParamStr("input")), xml_w);
//			} else {
//				saxParser.parse(new File("groups.xml"), xml_w);
//			}

			saxParser.parse(new File("groups.xml"), xml_w);

		} catch (Throwable t) {
			t.printStackTrace();
		}

		/*
		 * XMLWorker xml_w = new XMLWorker(); xml_w.saveClusters("out.xml",
		 * clusters_seq, clusters_evs);
		 */
	}
}
